<?php
session_start();
require 'php/db.php';
if (!isset($_SESSION['patient_id'])) { header("Location: landing_page.html"); exit; }
$patient_id = $_SESSION['patient_id'];

// ===============================================
// ✅ ONE-FILE API LOGIC (PATIENT SIDE)
// ===============================================
if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    $action = $_GET['action'];
    $TARGET_ADMIN = 'admin';
    $SENDER_ID = $patient_id;

    try {
        if ($action === 'load_messages') {
            // --- LOAD MESSAGES ---
            $stmt = $pdo->prepare("
                SELECT 
                    message_body, 
                    sender_id, 
                    timestamp
                FROM chat_messages
                WHERE (sender_id = :pid AND receiver_id = :admin) 
                   OR (sender_id = :admin AND receiver_id = :pid)
                ORDER BY timestamp ASC
            ");
            $stmt->execute([':pid' => $SENDER_ID, ':admin' => $TARGET_ADMIN]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $result = array_map(function($m) use ($SENDER_ID) {
                return [
                    'message' => $m['message_body'],
                    'sender_type' => $m['sender_id'] === $SENDER_ID ? 'user' : 'admin', 
                    'timestamp' => date('h:i A | M d', strtotime($m['timestamp']))
                ];
            }, $messages);

            echo json_encode($result);

        } elseif ($action === 'send_message' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            // --- SEND MESSAGE ---
            $message = trim($_POST['message'] ?? '');
            
            if (empty($message)) { http_response_code(400); echo json_encode(['error' => 'Message is empty']); exit; }

            $stmt = $pdo->prepare("
                INSERT INTO chat_messages (sender_id, receiver_id, message_body)
                VALUES (:sender, :receiver, :message)
            ");
            $stmt->execute([
                ':sender' => $SENDER_ID,
                ':receiver' => $TARGET_ADMIN,
                ':message' => $message
            ]);

            echo json_encode(['success' => true]);

        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Invalid action']);
        }

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }

    exit; // Exit after API response
}
// ===============================================
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>User Inbox — Barangay Ilaya Health Center</title>

  <link rel="stylesheet" href="css/inbox.css">
  <link rel="stylesheet" href="css/navbar.css"> 
</head>
<body>

<header class="navbar">
  <div class="logo">
    <img src="https://i.ibb.co/qFPvFtms/167565708-10159274632132790-1386372340681598199-n-removebg-preview.png" alt="Logo">
    <h1>BARANGAY ILAYA HEALTH CENTER</h1>
  </div>
  <nav class="nav-main">
    <ul class="nav-links">
      <li><a href="index.html">Home</a></li>
      <li><a href="new about us.html">About Us</a></li>
      <li><a href="new services.html">Services</a></li>
    </ul>

    <div class="dropdown">
      <button id="menuButton" class="dropbtn">Menu ▼</button>
      <div class="dropdown-content" id="dropdownMenu">
        <a href="book appointment.html">Book Appointment</a>
        <a href="my_appointment.html">My Appointment</a>
        <a href="inbox.php">Inbox</a>
      </div>
    </div>

    <div class="nav-profile-wrapper">
      <button id="navProfileBtn" class="profile-btn" aria-haspopup="true" aria-expanded="false">
        <img id="navProfileImage" src="uploads/default_profile.png" alt="Profile" class="nav-profile">
      </button>
      <div id="navProfileDropdown" class="nav-profile-dropdown" hidden>
        <div class="nav-profile-info">
          <strong id="navProfileName">Guest</strong>
          <div id="navPatientId" class="nav-pid">PTN-0000</div>
        </div>
        <hr>
        <a href="account.html">Account Settings</a>
        <button id="toggleDark" class="linkish">Toggle Dark Mode</button>
        <a href="php/logout.php" class="logout-btn">Log Out</a>
      </div>
    </div>
  </nav>
</header>


<main class="page-wrap">
  <section class="inbox-container">
    <aside class="left-panel">
      <div class="tabs">
        <button class="tab-btn active" data-tab="messages">Messages</button>
        <button class="tab-btn" data-tab="reminders">Reminders</button>
        <button class="tab-btn" data-tab="archive">Archive</button>
      </div>

      <div id="tabContent-messages" class="tab-content active">
        <h4>Admin</h4>
        <div id="adminList" class="user-list">
          <div class="user-item">Health Center Admin</div>
        </div>
      </div>
      <div id="tabContent-reminders" class="tab-content"><p>No reminders yet.</p></div>
      <div id="tabContent-archive" class="tab-content"><p>No archived messages.</p></div>
    </aside>

    <section class="chat-panel">
      <div class="chat-header" id="chatHeader">
        <h3>Barangay Health Center Admin</h3>
        <div id="typingIndicator" class="typing-indicator">Typing...</div>
      </div>

      <div class="chat-messages" id="chatMessages"></div>

      <div class="chat-input">
        <input id="msgInput" placeholder="Type your message...">
        <button id="sendBtn">Send</button>
        <button id="archiveBtn" class="archive-btn">Archive</button>
      </div>
    </section>
  </section>
</main>

<script>
// ===============================================
// ✅ PATIENT INBOX CHAT LOGIC
// ===============================================
const chatMessages = document.getElementById('chatMessages');
const msgInput = document.getElementById('msgInput');
const sendBtn = document.getElementById('sendBtn');
const INBOX_PHP_URL = 'inbox.php'; 

/* -------------------------
   LOAD MESSAGES
-------------------------- */
function loadMessages() {
    fetch(`${INBOX_PHP_URL}?action=load_messages`)
    .then(r => r.json())
    .then(msgs => {
        if (msgs.error) {
            console.error(msgs.error);
            return;
        }

        const currentScroll = chatMessages.scrollHeight - chatMessages.clientHeight - chatMessages.scrollTop;
        const shouldScroll = currentScroll <= 20 || msgs.length === 0;

        chatMessages.innerHTML = "";

        msgs.forEach(m => {
            const div = document.createElement("div");
            div.className = `msg ${m.sender_type}`; 
            div.innerHTML = `${m.message}<br><span>${m.timestamp}</span>`;
            chatMessages.appendChild(div);
        });

        if (shouldScroll) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    })
    .catch(e => console.error("Error loading messages:", e));
}

/* -------------------------
   SEND MESSAGE
-------------------------- */
sendBtn.onclick = function () {
    const msg = msgInput.value.trim();

    if (!msg) return;

    fetch(`${INBOX_PHP_URL}?action=send_message`, {
        method: "POST",
        headers: {"Content-Type":"application/x-www-form-urlencoded"},
        body: `message=${encodeURIComponent(msg)}` 
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            msgInput.value = "";
            loadMessages(); 
        } else {
            alert("Failed to send message: " + (data.error || 'Unknown error'));
        }
    })
    .catch(e => console.error("Error sending message:", e));
};

msgInput.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        sendBtn.click();
    }
});

// --- INIT CHAT ---
loadMessages(); 
setInterval(loadMessages, 1200); 

// Tab switching logic (kept from original inbox.php)
document.querySelectorAll('.tab-btn').forEach(button => {
    button.addEventListener('click', function() {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        this.classList.add('active');
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
        document.getElementById(`tabContent-${this.dataset.tab}`).classList.add('active');
    });
});

// ===============================================
// ✅ PATIENT NAVBAR DROPDOWN/PROFILE LOGIC 
// (COPIED FROM LANDING PAGE)
// ===============================================

// Menu dropdown logic
(function(){
  const menuButton = document.getElementById('menuButton');
  const dropdownMenu = document.getElementById('dropdownMenu');
  if (!menuButton || !dropdownMenu) return;

  menuButton.addEventListener('click', function(e){
    e.stopPropagation();
    const opened = dropdownMenu.classList.toggle('show');
    menuButton.setAttribute('aria-expanded', String(opened));
  });

  dropdownMenu.addEventListener('click', function(e){ e.stopPropagation(); });

  window.addEventListener('click', function(){
    if (dropdownMenu.classList.contains('show')){
      dropdownMenu.classList.remove('show');
      menuButton.setAttribute('aria-expanded', 'false');
    }
  });
})();

// Navbar profile dropdown & populate
(function(){
  const btn = document.getElementById('navProfileBtn');
  const dd = document.getElementById('navProfileDropdown');
  const img = document.getElementById('navProfileImage');
  const nameEl = document.getElementById('navProfileName');
  const pidEl = document.getElementById('navPatientId');
  
  btn && btn.addEventListener('click', function(e){ e.stopPropagation(); const open = dd.hidden; dd.hidden = !open; btn.setAttribute('aria-expanded', String(!open)); });
  window.addEventListener('click', ()=>{ if(dd) dd.hidden = true; btn && btn.setAttribute('aria-expanded','false'); });

  // populate user info (fetch('php/get_user.php') logic)
  fetch('php/get_user.php').then(r=>r.json()).then(data=>{
    if(!data || data.error) return;
    if(img) img.src = data.profile_pic && data.profile_pic.trim() !== '' ? (data.profile_pic + '?v=' + Date.now()) : (data.gender && data.gender.toLowerCase().startsWith('m') ? 'uploads/default_male.svg' : (data.gender && data.gender.toLowerCase().startsWith('f') ? 'uploads/default_female.svg' : 'uploads/default_profile.png'));
    if(nameEl) nameEl.textContent = (data.first_name || '') + ' ' + (data.last_name || '');
    if(pidEl) pidEl.textContent = data.patient_id || '';
  }).catch(()=>{});

  // dark mode toggle (local only)
  const toggle = document.getElementById('toggleDark');
  toggle && toggle.addEventListener('click', ()=>{ document.body.classList.toggle('dark-mode'); });
})();
</script>

</body>
</html>