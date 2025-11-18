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
  <link rel="stylesheet" href="css/navbar.css">
</head>
<body>

<!-- ✅ SAME NAVBAR AS LANDING PAGE -->
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

      <div class="nav-menu">
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
  if (menuButton && dropdownMenu) {
    menuButton.addEventListener('click', () => dropdownMenu.classList.toggle('show'));
    window.addEventListener('click', (e) => {
      if (!menuButton.contains(e.target) && !dropdownMenu.contains(e.target)) {
        dropdownMenu.classList.remove('show');
      }
    });
  }
</script>

<script>
  (function(){
    const btn = document.getElementById('navProfileBtn');
    const dd = document.getElementById('navProfileDropdown');
    const img = document.getElementById('navProfileImage');
    const nameEl = document.getElementById('navProfileName');
    const pidEl = document.getElementById('navPatientId');
    btn && btn.addEventListener('click', function(e){ e.stopPropagation(); const open = dd.hidden; dd.hidden = !open; btn.setAttribute('aria-expanded', String(!open)); });
    window.addEventListener('click', ()=>{ if(dd) dd.hidden = true; btn && btn.setAttribute('aria-expanded','false'); });
    fetch('php/get_user.php').then(r=>r.json()).then(data=>{ if(!data || data.error) return; if(img) img.src = data.profile_pic && data.profile_pic.trim() !== '' ? (data.profile_pic + '?v=' + Date.now()) : (data.gender && data.gender.toLowerCase().startsWith('m') ? 'uploads/default_male.svg' : (data.gender && data.gender.toLowerCase().startsWith('f') ? 'uploads/default_female.svg' : 'uploads/default_profile.png')); if(nameEl) nameEl.textContent = (data.first_name || '') + ' ' + (data.last_name || ''); if(pidEl) pidEl.textContent = data.patient_id || ''; }).catch(()=>{});
    const toggle = document.getElementById('toggleDark'); toggle && toggle.addEventListener('click', ()=>{ document.body.classList.toggle('dark-mode'); });
  })();
</script>

</body>
</html>
