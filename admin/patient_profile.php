<?php
// admin/patient_profile.php
require __DIR__ . '/_auth_admin.php';
require __DIR__ . '/../php/db.php';

$patient_id = $_GET['patient_id'] ?? null;
if (!$patient_id) { 
    header('Location: patients.php'); 
    exit; 
}

// Fetch patient using patient_id (NOT user_id)
$stmt = $pdo->prepare("
    SELECT patient_id, first_name, middle_initial, last_name, user_role
    FROM users 
    WHERE patient_id = ?
    LIMIT 1
");
$stmt->execute([$patient_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Redirect if no user OR user role is not patient
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

// BASE directory for saved records
$base = dirname(__DIR__) . '/patient_records';

// Find folder with pattern: patientID_*
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

    // Sort by newest
    usort($raw, function($a, $b) use ($folder){
        return filemtime("$folder/$b") - filemtime("$folder/$a");
    });

    $files = $raw;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Patient Profile — <?= htmlspecialchars($patient_id) ?></title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link rel="stylesheet" href="css/sidebar.css">
  <link rel="stylesheet" href="css/patients.css">
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
      <a href="patients.php">← Back to Patients</a>

      <div class="profile-card">
        <h2>Patient</h2>
        <p><strong>Name:</strong> <?= htmlspecialchars($full_name) ?></p>
        <p><strong>Patient ID:</strong> <?= htmlspecialchars($user['patient_id']) ?></p>
      </div>

      <div class="profile-card files-list">
        <h3>Completed Appointments (Saved Files)</h3>
        
        <?php if (count($files)): ?>
          <ul>
            <?php foreach($files as $f): ?>
              <li>
                <span><?= htmlspecialchars($f) ?></span>
                <span class="file-actions">
                  <a href="../php/admin/serve_patient_file.php?patient=<?= urlencode($patient_id) ?>&file=<?= urlencode($f) ?>" target="_blank">View</a>
                  <a href="../php/admin/serve_patient_file.php?patient=<?= urlencode($patient_id) ?>&file=<?= urlencode($f) ?>&download=1">Download</a>
                </span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p><em>No completed appointment files yet.</em></p>
        <?php endif; ?>
      </div>
    </div>
  </main>
</body>
</html>
