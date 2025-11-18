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

    <!-- LEFT PANEL -->
    <div class="users-panel">
      <input type="text" id="userSearch" placeholder="Search user...">
      <div id="userList" class="user-list"></div>
    </div>

    <!-- RIGHT CHAT PANEL -->
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

/* -------------------------
   LOAD USERS
-------------------------- */
function loadUsers(search = "") {
    fetch("php/chat/fetch_conversations.php")
    .then(r => r.json())
    .then(users => {
        const list = document.getElementById("userList");
        list.innerHTML = "";

        users
        .filter(u => u.name.toLowerCase().includes(search.toLowerCase()))
        .forEach(u => {
            const div = document.createElement("div");
            div.className = "user";
            div.textContent = u.name;
            div.onclick = () => selectUser(u.patient_id, u.name);
            list.appendChild(div);
        });
    });
}

document.getElementById("userSearch").addEventListener("input", e => {
    loadUsers(e.target.value);
});

/* -------------------------
   SELECT USER
-------------------------- */
function selectUser(pid, name) {
    currentUser = pid;
    document.getElementById("chatHeader").innerHTML = `<h3>${name}</h3>`;
    loadMessages();
}

/* -------------------------
   LOAD MESSAGES
-------------------------- */
function loadMessages() {
    if (!currentUser) return;

    fetch(`php/chat/fetch_messages.php?patient_id=${currentUser}`)
    .then(r => r.json())
    .then(msgs => {
        const box = document.getElementById("chatMessages");
        box.innerHTML = "";

        msgs.forEach(m => {
            const div = document.createElement("div");
            div.className = `message ${m.sender_type}`;
            div.innerHTML = `
                <p>${m.message}</p>
                <span>${m.timestamp}</span>
            `;
            box.appendChild(div);
        });

        box.scrollTop = box.scrollHeight;
    });
}

/* -------------------------
   SEND MESSAGE
-------------------------- */
document.getElementById("sendBtn").onclick = function () {
    const input = document.getElementById("msgInput");
    const msg = input.value.trim();

    if (!msg || !currentUser) return;

    fetch("php/chat/send_message.php", {
        method: "POST",
        headers: {"Content-Type":"application/x-www-form-urlencoded"},
        body: `patient_id=${currentUser}&message=${encodeURIComponent(msg)}&sender=admin`
    })
    .then(() => {
        input.value = "";
        loadMessages();
    });
};

/* -------------------------
   AUTO REFRESH
-------------------------- */
setInterval(loadMessages, 1200);

// INIT
loadUsers();
</script>

</body>
</html>
