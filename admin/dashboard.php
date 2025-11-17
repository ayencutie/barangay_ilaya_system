<?php
require __DIR__ . '/_auth_admin.php';
require __DIR__ . '/../php/db.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Admin Dashboard — Barangay Ilaya</title>
  <link rel="stylesheet" href="css/sidebar.css">
  <link rel="stylesheet" href="css/dashboard.css">
</head>
<body class="<?= !empty($admin['dark_mode']) ? 'dark' : '' ?>">

  <aside class="sidebar">
    <h2>Barangay Ilaya</h2>
    <ul class="nav">
      <li><a href="dashboard.php" class="active">Dashboard</a></li>
      <li><a href="appointments.php">Appointments</a></li>
      <li><a href="patients.php">Patients</a></li>
      <li><a href="admin_inbox.php">Inbox</a></li>
      <li><a href="reports.php">Reports & Analytics</a></li>
      <li><a href="settings.php">Settings</a></li>
      <li><a href="../php/logout.php">Logout</a></li>
    </ul>
  </aside>

  <main class="main">
    <div class="header">
      <h1>Dashboard Overview</h1>
      <input type="text" placeholder="Search here..." class="search" id="adminSearch">
    </div>

    <section class="stats-grid">
      <div class="card">
        <h3>Total Patients</h3>
        <p class="number" id="totalPatients">—</p>
      </div>
      <div class="card">
        <h3>Today's Appointments</h3>
        <p class="number" id="todayAppointments">—</p>
      </div>
      <div class="card">
        <h3>Completed Appointments</h3>
        <p class="number" id="completedAppointments">—</p>
      </div>
    </section>

    <section class="appointments">
      <h2>Appointments for Approval</h2>
      <table id="pendingTable">
        <thead>
          <tr>
            <th>Appointment ID</th>
            <th>Patient Name</th>
            <th>Service</th>
            <th>Time</th>
            <th>Date</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </section>

    <section class="upcoming">
      <h2>Upcoming Appointments</h2>
      <div class="upcoming-list" id="upcomingList"></div>
    </section>
  </main>

  <script src="js/dashboard.js"></script>
</body>
</html>
