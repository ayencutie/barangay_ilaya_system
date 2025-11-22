<?php
session_start();
require 'php/db.php';
if (!isset($_SESSION['patient_id'])) { header("Location: landing_page.html"); exit; }
$patient_id = $_SESSION['patient_id'];

// ===============================================
// ✅ API LOGIC (UNCHANGED)
// ===============================================
if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    $action = $_GET['action'];
    $TARGET_ADMIN = 'admin';
    $SENDER_ID = $patient_id;

    try {
        if ($action === 'load_messages') {
            $stmt = $pdo->prepare("
                SELECT message_body, sender_id, timestamp
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
                    'sender_type' => $m['sender_id'] === $SENDER_ID ? 'patient' : 'admin',
                    'timestamp' => date('h:i A', strtotime($m['timestamp']))
                ];
            }, $messages);

            echo json_encode(['status' => 'success', 'data' => $result]);
            exit;

        } elseif ($action === 'send_message') {
            $input = json_decode(file_get_contents('php://input'), true);
            $msg = trim($input['message'] ?? '');

            if (!empty($msg)) {
                $stmt = $pdo->prepare("INSERT INTO chat_messages (sender_id, receiver_id, message_body) VALUES (?, ?, ?)");
                $stmt->execute([$SENDER_ID, $TARGET_ADMIN, $msg]);
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Empty message']);
            }
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inbox - Patient Chat</title>
    <link rel="stylesheet" href="css/inbox.css">
</head>
<body>

    <header class="navbar">
        
        <div class="nav-left">
            <img src="https://i.ibb.co/qFPvFtms/167565708-10159274632132790-1386372340681598199-n-removebg-preview.png" alt="Logo">
            <h1>BARANGAY ILAYA HEALTH CENTER</h1>
        </div>

        <div class="nav-center">
            <ul class="nav-links">
                <li><a href="index.html">Home</a></li>
                <li><a href="new about us.html">About Us</a></li>
                <li><a href="new services.html">Services</a></li>
            </ul>
        </div>

        <div class="nav-right">
            
            <a href="inbox.php" class="icon-btn" title="Inbox">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                </svg>
                <span id="msgBadge" class="badge">0</span>
            </a>

            <div class="dropdown-container">
                <button id="menuButton" class="menu-btn">Menu ▼</button>
                <div id="menuDropdown" class="dropdown-content">
                    <a href="book appointment.html">Book Appointment</a>
                    <a href="my_appointment.html">My Appointment</a>
                    
                </div>
            </div>

            <div class="profile-container">
                <button id="profileButton" class="profile-btn" title="Account">
                    <img id="userAvatar" src="uploads/default_profile.png" alt="Profile">
                    <span class="active-dot"></span> 
                </button>

                <div id="profileDropdown" class="profile-card" hidden>
                    <div class="profile-header">
                        <img id="dropdownAvatar" src="uploads/default_profile.png" alt="User">
                        <div class="header-info">
                            <strong id="userName">Guest User</strong>
                            <span id="userId">Patient ID: --</span>
                        </div>
                    </div>
                    <hr class="divider">
                    <div class="profile-links">
                        <a href="account.html" class="p-link"><span class="icon"></span> Account Settings</a>
                        <hr class="divider-small">
                        <a href="php/logout.php" class="p-link logout"><span class="icon"></span> Log Out</a>
                    </div>
                </div>
            </div>

        </div> 
    </header>
    <div class="chat-wrapper">
        <div class="chat-container">
            
            <div class="chat-header">
                <div class="admin-info">
                    <div class="avatar-circle">
                        <img src="https://i.ibb.co/qFPvFtms/167565708-10159274632132790-1386372340681598199-n-removebg-preview.png" alt="Admin">
                        <span class="status-indicator"></span>
                    </div>
                    <div class="details">
                        <h3>Health Center Admin</h3>
                        <p>Online • Replies typically in minutes</p>
                    </div>
                </div>
            </div>

            <div class="messages-area" id="chatMessages">
                <div class="empty-state">Loading messages...</div>
            </div>

            <div class="input-area">
                <input type="text" id="messageInput" placeholder="Type your message here..." autocomplete="off">
                <button onclick="sendMessage()" id="sendBtn">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                        <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                    </svg>
                </button>
            </div>

        </div>
    </div>

    <div id="toastBox" class="toast-container"></div>
    <audio id="notifSound" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" preload="auto"></audio>

    <script>
        // 1. NAVBAR LOGIC
        const menuBtn = document.getElementById('menuButton');
        const menuDropdown = document.getElementById('menuDropdown');
        const profileBtn = document.getElementById('profileButton');
        const profileDropdown = document.getElementById('profileDropdown');

        function closeAll() {
            if(menuDropdown) menuDropdown.classList.remove('show');
            if(profileDropdown) profileDropdown.hidden = true;
        }

        if(menuBtn) {
            menuBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                const isOpen = menuDropdown.classList.contains('show');
                closeAll();
                if(!isOpen) menuDropdown.classList.add('show');
            });
        }

        if(profileBtn) {
            profileBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                const isOpen = !profileDropdown.hidden;
                closeAll();
                if(!isOpen) profileDropdown.hidden = false;
            });
        }

        window.addEventListener('click', closeAll);

        // 2. USER INFO
        fetch('php/get_user.php').then(r=>r.json()).then(data => {
            if(data && !data.error) {
                const picUrl = data.profile_pic || 'uploads/default_profile.png';
                if(document.getElementById('userAvatar')) document.getElementById('userAvatar').src = picUrl;
                if(document.getElementById('dropdownAvatar')) document.getElementById('dropdownAvatar').src = picUrl;
                if(document.getElementById('userName')) document.getElementById('userName').innerText = data.first_name || 'User';
                if(document.getElementById('userId')) document.getElementById('userId').innerText = data.patient_id || '';
            }
        }).catch(() => {});

        // 3. NOTIFICATION SIMULATION
        function triggerNotification(message) {
            const badge = document.getElementById('msgBadge');
            const toastBox = document.getElementById('toastBox');
            const audio = document.getElementById('notifSound');

            if(badge) { 
                badge.style.display = 'flex'; 
                badge.innerText = '1'; 
            }
            
            if(audio) audio.play().catch(() => {}); 

            const toast = document.createElement('div');
            toast.className = 'toast';
            toast.innerHTML = `<div class="toast-icon">📩</div><div class="toast-msg"><h4>New Message</h4><p>${message}</p></div>`;
            toastBox.appendChild(toast);

            setTimeout(() => toast.classList.add('show'), 100);
            setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 500); }, 5000);
        }

        // Simulate Notification after 3s
        setTimeout(() => { 
            triggerNotification("You have a new message from Admin."); 
        }, 3000);

        // 4. CHAT LOGIC
        const chatBox = document.getElementById('chatMessages');
        const input = document.getElementById('messageInput');

        async function loadMessages() {
            try {
                const res = await fetch('?action=load_messages');
                const json = await res.json();
                
                if (json.status === 'success') {
                    // If empty, remove loading text
                    if(chatBox.innerHTML.includes("Loading messages")) chatBox.innerHTML = '';

                    // Simple diff check (can be improved) or just clear/redraw for simplicity
                    // For smoother UX, usually we append, but clearing is safer for now
                    chatBox.innerHTML = ''; 
                    
                    json.data.forEach(msg => {
                        const div = document.createElement('div');
                        div.className = `msg ${msg.sender_type === 'patient' ? 'sent' : 'received'}`;
                        div.innerHTML = `
                            <div class="bubble">${msg.message}</div>
                            <span class="msg-time">${msg.timestamp}</span>
                        `;
                        chatBox.appendChild(div);
                    });
                    
                    // Keep scrolled to bottom
                    chatBox.scrollTop = chatBox.scrollHeight;
                }
            } catch(e) { console.error(e); }
        }

        async function sendMessage() {
            const text = input.value.trim();
            if (!text) return;

            try {
                const res = await fetch('?action=send_message', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ message: text })
                });
                const json = await res.json();
                
                if (json.status === 'success') {
                    input.value = '';
                    loadMessages(); 
                }
            } catch(e) { console.error(e); }
        }

        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') sendMessage();
        });

        // Refresh chat every 2 seconds
        setInterval(loadMessages, 2000);
        loadMessages(); 

    </script>

</body>
</html>