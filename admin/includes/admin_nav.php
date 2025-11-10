<?php
// Make sure admin is logged in
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
?>

<header class="navbar">
  <div class="logo">
    <img src="https://i.ibb.co/zVbCpfct/167565708-10159274632132790-1386372340681598199-n-removebg-preview.png" alt="Admin Logo">
    <h1>Admin Panel - Barangay Ilaya Health Center</h1>
  </div>
  <nav>
    <ul>
      <li><a href="index.php">Dashboard</a></li>
      <li><a href="appointments.php">Appointments</a></li>
      <li><a href="users.php">Users</a></li>
      <li><a href="messages.php">Messages</a></li>
      <li><a href="logout.php">Logout</a></li>
    </ul>
  </nav>
</header>
