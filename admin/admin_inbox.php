<?php
require __DIR__ . '/_auth_admin.php';
require __DIR__ . '/../php/db.php';
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
<body>

  <!-- SIDEBAR -->
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

  <!-- MAIN PANEL -->
  <main class="main">
    <div class="header">
      <h1>Inbox</h1>
    </div>

    <div class="inbox-wrapper">

      <!-- LEFT USER LIST -->
      <div class="users-panel">
        <input type="text" id="userSearch" placeholder="Search user...">
        <div id="userList" class="user-list"></div>
      </div>

      <!-- RIGHT CHAT WINDOW -->
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
let currentUser = null;

// Load all users
function loadUsers(search=''){
    fetch('../php/fetch_users.php')
    .then(r => r.json())
    .then(data => {
        const list = document.getElementById('userList');
        list.innerHTML = '';
        data
        .filter(u =>
            u.first_name.toLowerCase().includes(search.toLowerCase()) ||
            u.last_name.toLowerCase().includes(search.toLowerCase())
        )
        .forEach(user => {
            const div = document.createElement('div');
            div.className = 'user';
            div.textContent = `${user.first_name} ${user.last_name}`;
            div.dataset.id = user.patient_id;
            div.onclick = () => selectUser(user.patient_id, `${user.first_name} ${user.last_name}`);
            list.appendChild(div);
        });
    });
}

document.getElementById('userSearch').addEventListener('input', e => {
    loadUsers(e.target.value);
});

// Select user
function selectUser(patient_id, name){
    currentUser = patient_id;
    document.getElementById('chatHeader').innerHTML = `<h3>${name}</h3>`;
    loadMessages();
}

// Load messages
function loadMessages(){
    if (!currentUser) return;

    fetch(`../php/fetch_messages.php?patient_id=${currentUser}`)
    .then(r => r.json())
    .then(data => {
        const box = document.getElementById('chatMessages');
        box.innerHTML = '';

        data.forEach(msg => {
            const div = document.createElement('div');
            div.className = 'message ' + msg.sender_type;
            div.innerHTML = `<p>${msg.message}</p><span>${msg.created_at}</span>`;
            box.appendChild(div);
        });

        box.scrollTop = box.scrollHeight;
    });
}

// Send message
document.getElementById('sendBtn').onclick = function(){
    const msg = document.getElementById('msgInput').value.trim();
    if (!msg || !currentUser) return;

    fetch('../php/send_message.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:`patient_id=${currentUser}&message=${encodeURIComponent(msg)}&sender=admin`
    }).then(() => {
        document.getElementById('msgInput').value = '';
        loadMessages();
    });
};

// Auto refresh messages
setInterval(loadMessages,1500);

// Initial load
loadUsers();
</script>

</body>
</html>
