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
  <title>User Inbox — Barangay Ilaya</title>
  <link rel="stylesheet" href="css/inbox.css">
</head>
<body>
  <!-- your navbar (omitted for brevity) -->
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

      <section class="chat-panel">
        <div class="chat-header" id="chatHeader"><h3>Select an Admin</h3><div id="typingIndicator" style="display:none;">Typing...</div></div>
        <div class="chat-messages" id="chatMessages"></div>
        <div class="chat-input">
          <input id="msgInput" placeholder="Type your message...">
          <button id="sendBtn">Send</button>
          <button id="archiveBtn">Archive</button>
        </div>
      </section>
    </section>
  </main>

<script>
const PATIENT_ID = <?= json_encode($patient_id) ?>;
</script>
<script src="js/inbox.js"></script>
</body>
</html>
