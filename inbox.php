<?php
session_start();
require 'php/db.php';
if (!isset($_SESSION['patient_id'])) { header("Location: landing_page.html"); exit; }
$patient_id = $_SESSION['patient_id'];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>User Inbox — Barangay Ilaya Health Center</title>

  <!-- NEW STYLE -->
  <link rel="stylesheet" href="css/inbox.css">
</head>
<body>

<!-- ✅ SAME NAVBAR AS LANDING PAGE -->
<header class="navbar">
  <div class="logo">
    <img src="https://i.ibb.co/qFPvFtms/167565708-10159274632132790-1386372340681598199-n-removebg-preview.png" alt="Logo">
    <h1>BARANGAY ILAYA HEALTH CENTER</h1>
  </div>
  <nav>
    <ul>
      <li><a href="index.html">HOME</a></li>
      <li><a href="new about us.html">ABOUT US</a></li>
      <li><a href="new services.html">SERVICES</a></li>

      <li class="dropdown">
        <button class="dropbtn" id="menuButton">MENU ▼</button>
        <div class="dropdown-content" id="dropdownMenu">
          <a href="book appointment.html">Book Appointment</a>
          <a href="my_appointment.html">My Appointment</a>
          <a href="inbox.php" class="active">Inbox</a>
          <a href="account.html">Account</a>
          <a href="php/logout.php" class="logout-btn">Log Out</a>
        </div>
      </li>

    
    </ul>
  </nav>
</header>

<!-- PAGE WRAPPER -->
<main class="page-wrap">

  <section class="inbox-container">

    <!-- LEFT SIDE -->
    <aside class="left-panel">
      <div class="tabs">
        <button class="tab-btn active" data-tab="messages">Messages</button>
        <button class="tab-btn" data-tab="reminders">Reminders</button>
        <button class="tab-btn" data-tab="archive">Archive</button>
      </div>

      <div id="tabContent-messages" class="tab-content active">
        <h4>Admin</h4>
        <div id="adminList" class="user-list"></div>
      </div>

      <div id="tabContent-reminders" class="tab-content">
        <h4>Reminders</h4>
        <div id="remindersList"></div>
      </div>

      <div id="tabContent-archive" class="tab-content">
        <h4>Archived</h4>
        <div id="archiveList"></div>
      </div>
    </aside>

    <!-- RIGHT SIDE CHAT -->
    <section class="chat-panel">
      <div class="chat-header" id="chatHeader">
        <h3>Select an Admin</h3>
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
const PATIENT_ID = <?= json_encode($patient_id) ?>;
</script>
<script src="js/inbox.js"></script>

<script>
  const menuButton = document.getElementById('menuButton');
  const dropdownMenu = document.getElementById('dropdownMenu');
  menuButton.addEventListener('click', () => dropdownMenu.classList.toggle('show'));
  window.addEventListener('click', (e) => {
    if (!menuButton.contains(e.target) && !dropdownMenu.contains(e.target)) {
      dropdownMenu.classList.remove('show');
    }
  });
</script>

</body>
</html>
