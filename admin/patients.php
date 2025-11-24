<?php
// admin/patients.php
require __DIR__ . '/_auth_admin.php';
require __DIR__ . '/../php/db.php';

// fetch ONLY patients (exclude admin)
$stmt = $pdo->prepare("
    SELECT patient_id, first_name, middle_initial, last_name 
    FROM users 
    WHERE user_role = 'patient'
    ORDER BY last_name, first_name
");
$stmt->execute();
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Patients — Admin</title>
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
      <li><a href="admin_inbox.php">Inbox</a></li>
      <li><a href="reports.php">Reports & Analytics</a></li>
      <li><a href="settings.php">Settings</a></li>
      <li><a href="../php/logout.php">Logout</a></li>
    </ul>
  </aside>

  <main class="main">
    <div class="header">
      <h1>Patient History</h1>
      <input id="search" class="search" placeholder="Search patient..." />
    </div>

    <section class="patient-list" id="patientList">
      <?php foreach($patients as $p):
        $full = trim($p['first_name'] . ($p['middle_initial'] ? ' '.$p['middle_initial'].'.' : '') . ' ' . $p['last_name']);
        $initial = strtoupper(substr($p['first_name'],0,1));
      ?>
      <div class="patient-card" data-name="<?= htmlspecialchars(strtolower($full)) ?>" data-id="<?= htmlspecialchars($p['patient_id']) ?>">
        <div class="initial"><?= htmlspecialchars($initial) ?></div>
        <div class="info">
          <h3><?= htmlspecialchars($full) ?></h3>
          <p>Patient ID: <?= htmlspecialchars($p['patient_id']) ?></p>
        </div>
        <div class="actions">
          <a class="btn" href="patient_profile.php?patient_id=<?= urlencode($p['patient_id']) ?>">View Profile</a>
        </div>
      </div>
      <?php endforeach; ?>
    </section>
  </main>

  <script>
    // client-side search (by name or id)
    const search = document.getElementById('search');
    search.addEventListener('input', () => {
      const q = search.value.trim().toLowerCase();
      document.querySelectorAll('.patient-card').forEach(card => {
        const name = card.dataset.name || '';
        const id = card.dataset.id || '';
        card.style.display = (!q || name.includes(q) || id.toLowerCase().includes(q)) ? '' : 'none';
      });
    });
  </script>
</body>
</html>
