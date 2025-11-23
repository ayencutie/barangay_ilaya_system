<?php
// We trust that _auth_admin.php includes session_start()
require __DIR__ . '/_auth_admin.php'; 
require __DIR__ . '/../php/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$admin = $_SESSION['admin_user_data'] ?? []; 
$REAL_ADMIN_ID = isset($_SESSION['patient_id']) ? $_SESSION['patient_id'] : '22'; 
$SYSTEM_ADMIN_ID = 'admin'; 

// ===============================================
// ✅ ONE-FILE API LOGIC (ADMIN SIDE) - RETAINED
// ===============================================
if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        http_response_code(403); echo json_encode(['error' => 'Access denied']); exit;
    }

    $action = $_GET['action'];

    try {
        if ($action === 'load_conversations') {
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
                        CASE 
                            WHEN sender_id IN (:real_id, :sys_id) THEN receiver_id 
                            ELSE sender_id 
                        END AS patient_id_in_convo,
                        message_body,
                        timestamp,
                        ROW_NUMBER() OVER(PARTITION BY 
                            CASE 
                                WHEN sender_id IN (:real_id, :sys_id) THEN receiver_id 
                                ELSE sender_id 
                            END
                            ORDER BY timestamp DESC) as rn
                    FROM chat_messages
                    WHERE sender_id IN (:real_id, :sys_id) OR receiver_id IN (:real_id, :sys_id)
                ) t2 
                ON t1.patient_id = t2.patient_id_in_convo AND t2.rn = 1
                WHERE t1.user_role = 'patient'
                ORDER BY t2.timestamp DESC
            ");
            $stmt->execute([':real_id' => $REAL_ADMIN_ID, ':sys_id' => $SYSTEM_ADMIN_ID]);
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
            $pid = $_GET['patient_id'] ?? null;
            if (empty($pid)) { http_response_code(400); echo json_encode(['error' => 'Patient ID required']); exit; }

            $stmt = $pdo->prepare("
                SELECT 
                    message_body, 
                    sender_id, 
                    timestamp
                FROM chat_messages
                WHERE (sender_id = :pid AND receiver_id IN (:real_id, :sys_id)) 
                   OR (sender_id IN (:real_id, :sys_id) AND receiver_id = :pid)
                ORDER BY timestamp ASC
            ");
            $stmt->execute([':pid' => $pid, ':real_id' => $REAL_ADMIN_ID, ':sys_id' => $SYSTEM_ADMIN_ID]);
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
            $pid = $_POST['patient_id'] ?? null;
            $message = trim($_POST['message'] ?? '');
            
            if (empty($pid) || empty($message)) { http_response_code(400); echo json_encode(['error' => 'Missing data']); exit; }

            $SENDER_TO_USE = $SYSTEM_ADMIN_ID; 

            $stmt = $pdo->prepare("
                INSERT INTO chat_messages (sender_id, receiver_id, message_body)
                VALUES (:sender, :receiver, :message)
            ");
            $stmt->execute([
                ':sender' => $SENDER_TO_USE,
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

    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Admin Inbox — Barangay Ilaya</title>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
  <div class="inbox-wrapper">

    <div class="users-panel">
      <div class="panel-header">
        <h3>Inbox</h3>
      </div>
      <div class="search-box">
         <input type="text" id="userSearch" placeholder="Search patient...">
      </div>
      <div id="userList" class="user-list"></div>
    </div>

    <div class="chat-panel">
      <div class="chat-header" id="chatHeader">
        <div class="header-info">
            <h3 id="chatUserName">Select a User</h3>
            <span class="status-indicator"><span class="dot"></span> Online • Replies typically in minutes</span>
        </div>
      </div>

      <div class="chat-messages" id="chatMessages"></div>

      <div class="chat-input-area">
        <div class="input-wrapper">
            <input type="text" id="msgInput" placeholder="Type your message here...">
            <button id="sendBtn"><i class="fas fa-paper-plane"></i></button>
        </div>
      </div>
    </div>

  </div>
</main>

<script>
// ===============================================
// ✅ INBOX JAVASCRIPT LOGIC (ADMIN SIDE) - RETAINED
// ===============================================
let currentUser = null; 
const ADMIN_INBOX_PHP_URL = 'admin_inbox.php'; 

function loadConversations(search = "") {
    fetch(`${ADMIN_INBOX_PHP_URL}?action=load_conversations`)
    .then(r => r.json())
    .then(users => {
        const list = document.getElementById("userList");
        const currentSelection = currentUser;

        if (users.error) {
            list.innerHTML = `<p style="color:red;padding:10px;">Error: ${users.error}</p>`;
            return;
        }

        const filteredUsers = users.filter(u => u.name.toLowerCase().includes(search.toLowerCase()));
        list.innerHTML = "";

        if (filteredUsers.length === 0) {
            list.innerHTML = `<p style="padding:10px;color:#666;text-align:center;font-size:13px;">No conversations found.</p>`;
        }

        filteredUsers.forEach(u => {
            const div = document.createElement("div");
            div.className = `user ${u.patient_id === currentUser ? 'active-user' : ''}`;
            div.setAttribute('data-pid', u.patient_id); 
            div.innerHTML = `
                <div class="avatar-circle">${u.name.charAt(0)}</div>
                <div class="user-details">
                    <strong>${u.name}</strong>
                    <small>${u.last_message ? u.last_message.substring(0, 25) + (u.last_message.length > 25 ? '...' : '') : 'No messages'}</small>
                </div>
            `;
            div.onclick = () => selectUser(u.patient_id, u.name);
            list.appendChild(div);
        });
    })
    .catch(e => console.error("Error fetching conversations:", e));
}

document.getElementById("userSearch").addEventListener("input", e => {
    loadConversations(e.target.value);
});

function selectUser(pid, name) {
    if (currentUser === pid) return; 

    currentUser = pid;
    // Update Header Text specifically
    document.getElementById("chatUserName").innerText = name;
    
    document.querySelectorAll('.user').forEach(el => el.classList.remove('active-user'));
    const selectedEl = document.querySelector(`.user[data-pid="${pid}"]`);
    if(selectedEl) selectedEl.classList.add('active-user');
    
    loadMessages();
}

function loadMessages() {
    if (!currentUser) {
        document.getElementById("chatMessages").innerHTML = "<div class='empty-state'><p>Select a user to start chatting</p></div>";
        return;
    }

    fetch(`${ADMIN_INBOX_PHP_URL}?action=load_messages&patient_id=${currentUser}`)
    .then(r => r.json())
    .then(msgs => {
        if (msgs.error) return;
        
        const box = document.getElementById("chatMessages");
        const isNearBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 100;
        box.innerHTML = "";

        if (msgs.length === 0) {
             box.innerHTML = "<p style='text-align:center; padding-top:20px; color:#ccc;'>No messages yet.</p>";
        }

        msgs.forEach(m => {
            const div = document.createElement("div");
            div.className = `message ${m.sender_type === 'user' ? 'user' : 'admin'}`;
            // Removed internal timestamp for cleaner look, or can be added as hover
            div.innerHTML = `
                <div class="bubble">${m.message}</div>
                <div class="meta">${m.timestamp}</div>
            `;
            box.appendChild(div);
        });

        if (isNearBottom || msgs.length === 1) { 
            box.scrollTop = box.scrollHeight;
        }
    })
    .catch(e => console.error("Error fetching messages:", e));
}

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
            alert("Failed: " + (data.error || 'Unknown error'));
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

setInterval(() => {
    const searchVal = document.getElementById("userSearch").value;
    if (searchVal === "") loadConversations();
    if (currentUser) loadMessages();
}, 2000);

loadConversations();
</script>

</body>
</html>