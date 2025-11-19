<?php
// We trust that _auth_admin.php includes session_start()
require __DIR__ . '/_auth_admin.php'; 
require __DIR__ . '/../php/db.php';

// Assuming admin details are stored in session (set by _auth_admin.php)
// If _auth_admin.php doesn't set it, this is necessary for dark mode logic
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$admin = $_SESSION['admin_user_data'] ?? []; 
$ADMIN_ID = 'admin'; // Static ID for the Admin account

// ===============================================
// ✅ ONE-FILE API LOGIC (ADMIN SIDE)
// ===============================================
if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    // 2. Authentication Check
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        http_response_code(403); echo json_encode(['error' => 'Access denied']); exit;
    }

    $action = $_GET['action'];

    try {
        if ($action === 'load_conversations') {
            // --- LOAD CONVERSATIONS LIST ---
            $stmt = $pdo->prepare("
                SELECT 
                    t1.patient_id, 
                    t1.first_name, 
                    t1.last_name,
                    t2.message_body AS last_message,
                    t2.timestamp
                FROM users t1
                INNER JOIN (
                    SELECT 
                        CASE WHEN sender_id = :admin_id THEN receiver_id ELSE sender_id END AS patient_id_in_convo,
                        message_body,
                        timestamp,
                        ROW_NUMBER() OVER(PARTITION BY 
                            CASE WHEN sender_id = :admin_id THEN receiver_id ELSE sender_id END
                            ORDER BY timestamp DESC) as rn
                    FROM chat_messages
                    WHERE sender_id = :admin_id OR receiver_id = :admin_id
                ) t2 
                ON t1.patient_id = t2.patient_id_in_convo AND t2.rn = 1
                WHERE t1.user_role = 'patient'
                ORDER BY t2.timestamp DESC
            ");
            $stmt->execute([':admin_id' => $ADMIN_ID]);
            $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $result = array_map(function($c) {
                return [
                    'patient_id' => $c['patient_id'],
                    'name' => $c['first_name'] . ' ' . $c['last_name'],
                    'last_message' => $c['last_message'],
                    'timestamp' => $c['timestamp']
                ];
            }, $conversations);

            echo json_encode($result);

        } elseif ($action === 'load_messages') {
            // --- LOAD MESSAGES ---
            $pid = $_GET['patient_id'] ?? null;
            if (empty($pid)) { http_response_code(400); echo json_encode(['error' => 'Patient ID required']); exit; }

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
            $stmt->execute([':pid' => $pid, ':admin' => $ADMIN_ID]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $result = array_map(function($m) use ($pid) {
                return [
                    'message' => $m['message_body'],
                    'sender_type' => $m['sender_id'] === $pid ? 'user' : 'admin', 
                    'timestamp' => date('h:i A | M d', strtotime($m['timestamp']))
                ];
            }, $messages);

            echo json_encode($result);

        } elseif ($action === 'send_message' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            // --- SEND MESSAGE ---
            $pid = $_POST['patient_id'] ?? null;
            $message = trim($_POST['message'] ?? '');
            
            if (empty($pid) || empty($message)) { http_response_code(400); echo json_encode(['error' => 'Missing data']); exit; }

            $stmt = $pdo->prepare("
                INSERT INTO chat_messages (sender_id, receiver_id, message_body)
                VALUES (:sender, :receiver, :message)
            ");
            $stmt->execute([
                ':sender' => $ADMIN_ID,
                ':receiver' => $pid,
                ':message' => $message
            ]);

            echo json_encode(['success' => true]);

        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Invalid action']);
        }

    } catch (PDOException $e) {
        error_log("Admin Chat Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database operation failed.']);
    }

    exit; // EXIT HERE TO PREVENT HTML OUTPUT ON AJAX CALLS
}
// ===============================================
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Admin Inbox — Barangay Ilaya</title>

  <link rel="stylesheet" href="css/sidebar.css">
  <link rel="stylesheet" href="css/admin_inbox.css"> 
</head>
<body class="<?= !empty($admin['dark_mode']) ? 'dark' : '' ?>">

<aside class="sidebar">
  <h2>Barangay Ilaya</h2>
  <ul class="nav">
    <li><a href="dashboard.php">Dashboard</a></li>
    <li><a href="appointments.php">Appointments</a></li>
    <li><a href="patients.php">Patients</a></li>
    <li><a href="admin_inbox.php" class="active">Inbox</a></li> 
    <li><a href="reports.php">Reports & Analytics</a></li>
    <li><a href="settings.php">Settings</a></li>
    <li><a href="../php/logout.php">Logout</a></li>
  </ul>
</aside>

<main class="main">
  <div class="header">
    <h1>Inbox</h1> 
  </div>

  <div class="inbox-wrapper">

    <div class="users-panel">
      <input type="text" id="userSearch" placeholder="Search patient...">
      <div id="userList" class="user-list"></div>
    </div>

    <div class="chat-panel">
      <div class="chat-header" id="chatHeader">
        <h3>Select a User</h3>
      </div>

      <div class="chat-messages" id="chatMessages"></div>

      <div class="chat-input">
        <input type="text" id="msgInput" placeholder="Type your message...">
        <button id="sendBtn">Send</button>
      </div>
    </div>

  </div>
</main>

<script>
// ===============================================
// ✅ INBOX JAVASCRIPT LOGIC (ADMIN SIDE)
// ===============================================
let currentUser = null; 
const ADMIN_INBOX_PHP_URL = 'admin_inbox.php'; 

/* -------------------------
   LOAD CONVERSATIONS (Users)
-------------------------- */
function loadConversations(search = "") {
    fetch(`${ADMIN_INBOX_PHP_URL}?action=load_conversations`)
    .then(r => r.json())
    .then(users => {
        const list = document.getElementById("userList");
        list.innerHTML = "";

        if (users.error) {
            console.error("Conversation Error:", users.error);
            list.innerHTML = `<p style="color:red;padding:10px;">Error: ${users.error}</p>`;
            return;
        }

        const filteredUsers = users
            .filter(u => u.name.toLowerCase().includes(search.toLowerCase()));

        filteredUsers.forEach(u => {
            const div = document.createElement("div");
            div.className = `user ${u.patient_id === currentUser ? 'active-user' : ''}`;
            div.setAttribute('data-pid', u.patient_id); 
            div.innerHTML = `
                <strong>${u.name}</strong><br>
                <small>${u.last_message ? u.last_message.substring(0, 30) + (u.last_message.length > 30 ? '...' : '') : 'No messages yet'}</small>
            `;
            div.onclick = () => selectUser(u.patient_id, u.name);
            list.appendChild(div);
        });

        if (!currentUser && filteredUsers.length > 0) {
            selectUser(filteredUsers[0].patient_id, filteredUsers[0].name);
        }
    })
    .catch(e => console.error("Error fetching conversations:", e));
}

document.getElementById("userSearch").addEventListener("input", e => {
    loadConversations(e.target.value);
});

/* -------------------------
   SELECT USER
-------------------------- */
function selectUser(pid, name) {
    if (currentUser === pid) return; 

    currentUser = pid;
    document.getElementById("chatHeader").innerHTML = `<h3>${name}</h3>`;
    
    document.querySelectorAll('.user').forEach(el => el.classList.remove('active-user'));
    const selectedEl = document.querySelector(`.user[data-pid="${pid}"]`);
    if(selectedEl) selectedEl.classList.add('active-user');
    
    loadMessages();
}

/* -------------------------
   LOAD MESSAGES
-------------------------- */
function loadMessages() {
    if (!currentUser) {
        document.getElementById("chatMessages").innerHTML = "<p style='text-align:center;'>Select a user to view the chat.</p>";
        return;
    }

    fetch(`${ADMIN_INBOX_PHP_URL}?action=load_messages&patient_id=${currentUser}`)
    .then(r => r.json())
    .then(msgs => {
        if (msgs.error) {
            console.error("Messages Error:", msgs.error);
            return;
        }
        
        const box = document.getElementById("chatMessages");
        const currentScroll = box.scrollHeight - box.clientHeight - box.scrollTop;
        const shouldScroll = currentScroll <= 20 || msgs.length === 0;

        box.innerHTML = "";

        msgs.forEach(m => {
            const div = document.createElement("div");
            div.className = `message ${m.sender_type === 'user' ? 'user' : 'admin'}`;
            div.innerHTML = `
                <p>${m.message}</p>
                <span>${m.timestamp}</span>
            `;
            box.appendChild(div);
        });

        if (shouldScroll) {
            box.scrollTop = box.scrollHeight;
        }
    })
    .catch(e => console.error("Error fetching messages:", e));
}

/* -------------------------
   SEND MESSAGE
-------------------------- */
document.getElementById("sendBtn").onclick = function () {
    const input = document.getElementById("msgInput");
    const msg = input.value.trim();

    if (!msg || !currentUser) return;

    fetch(`${ADMIN_INBOX_PHP_URL}?action=send_message`, {
        method: "POST",
        headers: {"Content-Type":"application/x-www-form-urlencoded"},
        body: `patient_id=${currentUser}&message=${encodeURIComponent(msg)}` 
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            input.value = "";
            loadMessages();
            loadConversations(document.getElementById("userSearch").value); 
        } else {
            alert("Failed to send message: " + (data.error || 'Unknown error'));
        }
    })
    .catch(e => console.error("Error sending message:", e));
};

document.getElementById("msgInput").addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault(); 
        document.getElementById("sendBtn").click();
    }
});


/* -------------------------
   AUTO REFRESH (Polling)
-------------------------- */
setInterval(() => {
    const searchVal = document.getElementById("userSearch").value;
    if (searchVal === "") {
        loadConversations();
    }
    loadMessages();
}, 1500);

// INIT
loadConversations();
</script>

</body>
</html>