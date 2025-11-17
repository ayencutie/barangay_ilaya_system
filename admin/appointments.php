<?php
// /admin/appointments.php
require __DIR__ . '/_auth_admin.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Appointments — Admin</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link rel="stylesheet" href="css/sidebar.css">
  <link rel="stylesheet" href="css/appointments.css">
</head>
<body>
  <aside class="sidebar">
    <h2>Barangay Ilaya</h2>
    <ul class="nav">
      <li><a href="dashboard.php">Dashboard</a></li>
      <li><a href="appointments.php" class="active">Appointments</a></li>
      <li><a href="patients.php">Patients</a></li>
      <li><a href="admin_inbox.php">Inbox</a></li>
      <li><a href="reports.php">Reports & Analytics</a></li>
      <li><a href="settings.php">Settings</a></li>
      <li><a href="../php/logout.php">Logout</a></li>
    </ul>
  </aside>

  <main class="main">
    <div class="header">
      <h1>Appointments Management</h1>
      <input type="text" id="apptSearch" class="search" placeholder="Search by patient, service, date...">
    </div>

    <div class="controls">
      <div class="tabs" role="tablist" id="tabsWrap">
        <button class="tab-btn active" data-status="All">All</button>
        <button class="tab-btn" data-status="Upcoming">Upcoming</button>
        <button class="tab-btn" data-status="Pending">Pending</button>
        <button class="tab-btn" data-status="Approved">Approved</button>
        <button class="tab-btn" data-status="Completed">Completed</button>
        <button class="tab-btn" data-status="Missed">Missed</button>
        <button class="tab-btn" data-status="Cancelled">Cancelled</button>
      </div>

      <div class="actions">
        <button id="exportCsv" class="btn">Export CSV</button>
      </div>
    </div>

    <section class="table-wrap card">
      <div id="errorBanner" class="error-banner" style="display:none;"></div>

      <table id="appointmentsTable" aria-live="polite">
        <thead>
          <tr>
            <th>ID</th>
            <th>Patient</th>
            <th>Service</th>
            <th>Date</th>
            <th>Time</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <!-- filled by JS -->
        </tbody>
      </table>
    </section>

    <!-- EDIT MODAL -->
    <div id="editModal" class="modal" aria-hidden="true" role="dialog" aria-labelledby="editTitle">
      <div class="modal-content">
        <h3 id="editTitle">Edit Appointment</h3>
        <form id="editForm">
          <input type="hidden" name="appointment_id" id="editAppointmentId">
          <label for="editService">Service</label>
          <input id="editService" name="service" type="text" required>

          <label for="editDate">Date</label>
          <input id="editDate" name="date" type="date" required>

          <label for="editTime">Time slot</label>
          <input id="editTime" name="time_slot" type="text" required>

          <div class="modal-buttons">
            <button type="submit" class="btn">Save</button>
            <button type="button" id="closeModal" class="btn btn-secondary">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </main>

  <script src="js/appointments.js"></script>
</body>
</html>
