<?php
require __DIR__ . '/_auth_admin.php';
require __DIR__ . '/../php/db.php';

// --- Today's and Monthly summary queries ---
try {

    // Today
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) AS total,
            SUM(status = 'Completed') AS completed,
            SUM(status = 'Missed') AS missed,
            SUM(status = 'Cancelled') AS cancelled
        FROM appointments
        WHERE DATE(`date`) = CURDATE()
    ");
    $stmt->execute();
    $today = $stmt->fetch(PDO::FETCH_ASSOC);

    // Month
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) AS total,
            SUM(status = 'Completed') AS completed,
            SUM(status = 'Missed') AS missed,
            SUM(status = 'Cancelled') AS cancelled
        FROM appointments
        WHERE YEAR(`date`) = YEAR(CURDATE())
          AND MONTH(`date`) = MONTH(CURDATE())
    ");
    $stmt->execute();
    $month = $stmt->fetch(PDO::FETCH_ASSOC);

    // Overall
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM appointments");
    $overall = $stmt->fetch(PDO::FETCH_ASSOC);

    // Last 30 Days Table
    $stmt = $pdo->prepare("
        SELECT DATE(`date`) AS day,
               COUNT(*) AS total,
               SUM(status = 'Completed') AS completed,
               SUM(status = 'Missed') AS missed,
               SUM(status = 'Cancelled') AS cancelled
        FROM appointments
        WHERE `date` >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
        GROUP BY DATE(`date`)
        ORDER BY DATE(`date`) DESC
    ");
    $stmt->execute();
    $perDay = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Services
    $stmt = $pdo->query("
        SELECT service, COUNT(*) AS total
        FROM appointments
        GROUP BY service
        ORDER BY total DESC
        LIMIT 50
    ");
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Gender Demographics
    $stmt = $pdo->query("
        SELECT gender, COUNT(*) AS total
        FROM users
        WHERE user_role = 'patient'
        GROUP BY gender
    ");
    $genderStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Patient List
    $stmt = $pdo->query("
        SELECT patient_id, first_name, middle_initial, last_name, birthdate, gender, address, created_at
        FROM users
        WHERE user_role = 'patient'
        ORDER BY created_at DESC
        LIMIT 500
    ");
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Error fetching report data: " . htmlentities($e->getMessage()));
}

// Calculate age
function age_from_dob($dob){
    if (!$dob) return '';
    $d1 = new DateTime($dob);
    $d2 = new DateTime();
    return $d1->diff($d2)->y;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title>Reports & Analytics — Barangay Ilaya</title>
<meta name="viewport" content="width=device-width,initial-scale=1" />
<link rel="stylesheet" href="css/sidebar.css">
<link rel="stylesheet" href="css/reports.css">
</head>

<body>
<aside class="sidebar">
    <h2>Barangay Ilaya</h2>
    <ul class="nav">
      <li><a href="dashboard.php">Dashboard</a></li>
      <li><a href="appointments.php">Appointments</a></li>
      <li><a href="patients.php">Patients</a></li>
      <li><a href="admin_inbox.php">Inbox</a></li>
      <li><a href="reports.php" class="active">Reports & Analytics</a></li>
      <li><a href="settings.php">Settings</a></li>
      <li><a href="../php/logout.php">Logout</a></li>
    </ul>
</aside>

<main class="main">

    <!-- ► PRINT HEADER (AUTO-APPEARS ONLY WHEN PRINTING) -->
    <div class="print-header">
        <img src="https://i.ibb.co/qFPvFtms/167565708-10159274632132790-1386372340681598199-n-removebg-preview.png">
        <div class="ph-text">
            <h2>Barangay Ilaya Health Center</h2>
            <p>Official Generated Report</p>
            <p>Date Printed: <span id="printDate"></span></p>
        </div>
    </div>

    <!-- PAGE HEADER -->
    <div class="header">
        <h1>Reports & Analytics</h1>
        <div class="header-controls">
            <input id="reportSearch" type="text" placeholder="Search patient or service..." class="search">
            <button id="printBtn" class="btn">Print Selected</button>
        </div>
    </div>

    <!-- SUMMARY -->
    <section class="summary-grid">
      <div class="card summary">
        <h4>Today's Summary (<?= date('M d, Y') ?>)</h4>
        <div class="big"><?= intval($today['total']) ?></div>
        <div class="meta">
            <span>Completed: <?= intval($today['completed']) ?></span>
            <span>Missed: <?= intval($today['missed']) ?></span>
            <span>Cancelled: <?= intval($today['cancelled']) ?></span>
        </div>
      </div>

      <div class="card summary">
        <h4>This Month (<?= date('F Y') ?>)</h4>
        <div class="big"><?= intval($month['total']) ?></div>
        <div class="meta">
            <span>Completed: <?= intval($month['completed']) ?></span>
            <span>Missed: <?= intval($month['missed']) ?></span>
            <span>Cancelled: <?= intval($month['cancelled']) ?></span>
        </div>
      </div>

      <div class="card summary">
        <h4>Total Appointments</h4>
        <div class="big"><?= intval($overall['total']) ?></div>
        <div class="meta">All time</div>
      </div>
    </section>

    <!-- PRINT OPTIONS -->
    <section class="print-selection">
      <label><input type="checkbox" value="cards" checked> Summary Cards</label>
      <label><input type="checkbox" value="perday" checked> Last 30 days</label>
      <label><input type="checkbox" value="services" checked> Service Utilization</label>
      <label><input type="checkbox" value="patients" checked> Patient Demographics</label>
    </section>

    <!-- 30 DAYS TABLE -->
    <section class="report-section" data-print="perday">
      <h2>Appointments — Last 30 days</h2>
      <table class="striped" id="perDayTable">
        <thead>
        <tr><th>Date</th><th>Total</th><th>Completed</th><th>Missed</th><th>Cancelled</th></tr>
        </thead>
        <tbody>
        <?php foreach($perDay as $r): ?>
          <tr>
            <td><?= $r['day'] ?></td>
            <td><?= $r['total'] ?></td>
            <td><?= $r['completed'] ?></td>
            <td><?= $r['missed'] ?></td>
            <td><?= $r['cancelled'] ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </section>

    <!-- SERVICES -->
    <section class="report-section" data-print="services">
      <h2>Service Utilization</h2>
      <table class="striped" id="servicesTable">
        <thead><tr><th>Service</th><th>Total Appointments</th></tr></thead>
        <tbody>
        <?php foreach($services as $s): ?>
          <tr><td><?= $s['service'] ?></td><td><?= $s['total'] ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </section>

    <!-- DEMOGRAPHICS -->
    <section class="report-section" data-print="patients">
      <h2>Patient Demographics</h2>

      <div class="demographics">
        <div class="dem-box">
          <h4>By Gender</h4>
          <ul>
          <?php foreach($genderStats as $g): ?>
            <li><?= $g['gender'] ?: 'Unspecified' ?> — <?= $g['total'] ?></li>
          <?php endforeach; ?>
          </ul>
        </div>

        <div class="dem-box">
          <h4>Latest Registered Patients</h4>
          <table class="striped small" id="patientsTable">
            <thead>
            <tr><th>Patient ID</th><th>Name</th><th>Age</th><th>Gender</th><th>Registered</th></tr>
            </thead>
            <tbody>
            <?php foreach($patients as $p): ?>
              <tr>
                <td><?= $p['patient_id'] ?></td>
                <td><?= trim($p['first_name'].' '.($p['middle_initial']? $p['middle_initial'].". ":'').$p['last_name']) ?></td>
                <td><?= age_from_dob($p['birthdate']) ?></td>
                <td><?= $p['gender'] ?></td>
                <td><?= $p['created_at'] ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>

      </div>
    </section>

</main>

<script>
// PRINT BUTTON
document.getElementById('printBtn').addEventListener('click', () => {
    const checked = Array.from(document.querySelectorAll('.print-selection input:checked')).map(i=>i.value);

    document.querySelectorAll('[data-print]').forEach(sec=>{
        sec.style.display = checked.includes(sec.getAttribute('data-print')) ? '' : 'none';
    });

    document.querySelector('.header-controls').style.display = 'none';

    window.print();

    document.querySelectorAll('[data-print]').forEach(sec=>sec.style.display = '');
    document.querySelector('.header-controls').style.display = '';
});

// Search Filter
document.getElementById('reportSearch').addEventListener('input', function(){
    const q = this.value.toLowerCase();

    document.querySelectorAll('#servicesTable tbody tr, #patientsTable tbody tr, #perDayTable tbody tr')
        .forEach(r=>r.style.display = '');

    if (!q) return;

    document.querySelectorAll('#servicesTable tbody tr').forEach(r=>{
        r.style.display = r.cells[0].textContent.toLowerCase().includes(q) ? '' : 'none';
    });

    document.querySelectorAll('#patientsTable tbody tr').forEach(r=>{
        r.style.display = (r.cells[1].textContent + r.cells[0].textContent).toLowerCase().includes(q) ? '' : 'none';
    });

    document.querySelectorAll('#perDayTable tbody tr').forEach(r=>{
        r.style.display = r.cells[0].textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});

// Print date
document.getElementById("printDate").textContent = new Date().toLocaleDateString();
</script>

</body>
</html>
