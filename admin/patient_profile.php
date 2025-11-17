<?php
// admin/patient_profile.php
require __DIR__ . '/_auth_admin.php';
require __DIR__ . '/../php/db.php';

$patient_id = $_GET['patient_id'] ?? null;
if (!$patient_id) { 
    header('Location: patients.php'); 
    exit; 
}

// Fetch patient info
$stmt = $pdo->prepare("
    SELECT patient_id, first_name, middle_initial, last_name, user_role
    FROM users 
    WHERE patient_id = ?
    LIMIT 1
");
$stmt->execute([$patient_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Redirect if invalid patient
if (!$user || $user['user_role'] !== 'patient') {
    header('Location: patients.php');
    exit;
}

// Build full name
$full_name = trim(
    $user['first_name'] . 
    ($user['middle_initial'] ? ' '.$user['middle_initial'].'.' : '') . 
    ' ' . 
    $user['last_name']
);

// ----------- LOAD SAVED FILES (Completed Appointments Only) -----------
$base = dirname(__DIR__) . '/patient_records';

$folder = null;
foreach (glob($base . '/' . $patient_id . '_*') as $dir) {
    if (is_dir($dir)) {
        $folder = $dir;
        break;
    }
}

$files = [];
if ($folder && is_dir($folder)) {
    $raw = array_diff(scandir($folder), ['.','..']);

    usort($raw, function($a, $b) use ($folder){
        return filemtime("$folder/$b") - filemtime("$folder/$a");
    });

    $files = $raw;
}

// ----------- LOAD COMPLETED APPOINTMENTS ONLY -----------
$appt = $pdo->prepare("
    SELECT appointment_id, service, date, time_slot, status
    FROM appointments
    WHERE patient_id = ?
      AND status = 'completed'
    ORDER BY appointment_id DESC
");
$appt->execute([$patient_id]);
$appointments = $appt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Patient Profile — <?= htmlspecialchars($patient_id) ?></title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link rel="stylesheet" href="css/backbutton.css">
  <link rel="stylesheet" href="css/sidebar.css">
  <link rel="stylesheet" href="css/patients.css">

  <style>
    .appt-table { width:100%; border-collapse: collapse; margin-top:20px; }
    .appt-table th, .appt-table td {
        padding:12px; border-bottom:1px solid #ddd;
        text-align:left; font-size:15px;
    }
    .status-badge {
        padding:5px 10px; border-radius:5px;
        font-weight:bold; font-size:12px;
        color:#fff;
    }
    .completed { background:#2980b9; }
  </style>
</head>

<body>
  <aside class="sidebar">
    <h2>Barangay Ilaya</h2>
    <ul class="nav">
      <li><a href="dashboard.php">Dashboard</a></li>
      <li><a href="appointments.php">Appointments</a></li>
      <li><a href="patients.php" class="active">Patients</a></li>
      <li><a href="inbox.php">Inbox</a></li>
      <li><a href="reports.php">Reports & Analytics</a></li>
      <li><a href="settings.php">Settings</a></li>
      <li><a href="../php/logout.php">Logout</a></li>
    </ul>
  </aside>

  <main class="main">
    <div class="record" style="padding:28px;">
      <a href="patients.php" class="back-link">← Back to Patients</a>

      <div class="profile-card">
        <h2>Patient</h2>
        <p><strong>Name:</strong> <?= htmlspecialchars($full_name) ?></p>
        <p><strong>Patient ID:</strong> <?= htmlspecialchars($user['patient_id']) ?></p>
      </div>



      <!-- COMPLETED APPOINTMENT HISTORY -->
      <div class="profile-card">
        <h3>Completed Appointment History</h3>

        <?php if (count($appointments)): ?>
          <table class="appt-table">
            <tr>
              <th>Service</th>
              <th>Date</th>
              <th>Time</th>
              <th>Status</th>
            </tr>

            <?php foreach ($appointments as $a): ?>
              <tr>
                <td><?= htmlspecialchars($a['service']) ?></td>
                <td><?= htmlspecialchars($a['date']) ?></td>
                <td><?= htmlspecialchars($a['time_slot']) ?></td>
                <td>
                  <span class="status-badge completed">
                    Completed
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          </table>
        <?php else: ?>
          <p><em>No completed appointments yet.</em></p>
        <?php endif; ?>
      </div>

    </div>
  </main>
</body>
</html>
