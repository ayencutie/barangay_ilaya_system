<?php
// /php/fetch_reports.php
header('Content-Type: application/json');
session_start();
require __DIR__ . '/db.php';

// ADMIN AUTH: allow if admin session present
$isAdmin = false;
if (isset($_SESSION['admin_id'])) $isAdmin = true;
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') $isAdmin = true;
if (!$isAdmin) {
    echo json_encode(['error'=>'forbidden']);
    exit;
}

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1) Summary cards
    // total appointments
    $tot = (int)$pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();

    // completed
    $completed = (int)$pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'Completed'")->fetchColumn();

    // pending
    $pending = (int)$pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'Pending'")->fetchColumn();

    // today
    $today = (int)$pdo->prepare("SELECT COUNT(*) FROM appointments WHERE `date` = CURDATE()");
    $today->execute(); $todayCount = (int)$today->fetchColumn();

    // this month
    $thisMonth = (int)$pdo->prepare("SELECT COUNT(*) FROM appointments WHERE YEAR(`date`) = YEAR(CURDATE()) AND MONTH(`date`) = MONTH(CURDATE())");
    $thisMonth->execute(); $monthCount = (int)$thisMonth->fetchColumn();

    // missed
    $missed = (int)$pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'Missed'")->fetchColumn();

    // 2) Appointment summary (last 30 days) grouped by date
    $stmt = $pdo->prepare("
        SELECT `date`,
            COUNT(*) AS total,
            SUM(status = 'Completed') AS completed,
            SUM(status = 'Missed') AS missed,
            SUM(status = 'Cancelled') AS cancelled
        FROM appointments
        WHERE `date` >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
        GROUP BY `date`
        ORDER BY `date` ASC
    ");
    $stmt->execute();
    $apptsByDate = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // normalize to include missing dates (last 30 days)
    $days = [];
    for ($i = 29; $i >= 0; $i--) {
        $d = (new DateTime())->sub(new DateInterval("P{$i}D"))->format('Y-m-d');
        $days[$d] = ['date'=>$d,'total'=>0,'completed'=>0,'missed'=>0,'cancelled'=>0];
    }
    foreach ($apptsByDate as $r) {
        $days[$r['date']] = [
            'date'=>$r['date'],
            'total'=> (int)$r['total'],
            'completed'=> (int)$r['completed'],
            'missed'=> (int)$r['missed'],
            'cancelled'=> (int)$r['cancelled']
        ];
    }
    $apptsList = array_values($days);

    // 3) Patient demographics
    // gender counts
    $gstmt = $pdo->query("SELECT gender, COUNT(*) AS cnt FROM users GROUP BY gender");
    $genderCounts = $gstmt->fetchAll(PDO::FETCH_ASSOC);

    // age groups (based on birthdate)
    $astmt = $pdo->query("SELECT birthdate FROM users WHERE birthdate IS NOT NULL AND birthdate <> '0000-00-00'");
    $ages = [];
    while ($r = $astmt->fetch(PDO::FETCH_ASSOC)) {
        $bd = $r['birthdate'];
        if (!$bd) continue;
        $dob = new DateTime($bd);
        $now = new DateTime();
        $age = (int)$dob->diff($now)->y;
        $ages[] = $age;
    }
    // buckets: 0-17, 18-30, 31-45, 46-60, 61+
    $ageBuckets = ['0-17'=>0,'18-30'=>0,'31-45'=>0,'46-60'=>0,'61+'=>0];
    foreach ($ages as $a) {
        if ($a < 18) $ageBuckets['0-17']++;
        elseif ($a <= 30) $ageBuckets['18-30']++;
        elseif ($a <= 45) $ageBuckets['31-45']++;
        elseif ($a <= 60) $ageBuckets['46-60']++;
        else $ageBuckets['61+']++;
    }

    // zone counts: try to extract "Zone X" from address (fallback Unknown)
    $zones = [];
    $ustmt = $pdo->query("SELECT address FROM users WHERE address IS NOT NULL AND address <> ''");
    while ($r = $ustmt->fetch(PDO::FETCH_ASSOC)) {
        $addr = $r['address'];
        if (preg_match('/Zone\s*([0-9A-Za-z\-]+)/i', $addr, $m)) {
            $k = 'Zone ' . strtoupper($m[1]);
        } else {
            $k = 'Unknown';
        }
        if (!isset($zones[$k])) $zones[$k] = 0;
        $zones[$k]++;
    }
    arsort($zones);

    // 4) Service utilization
    $ss = $pdo->query("
        SELECT service, COUNT(*) AS cnt
        FROM appointments
        GROUP BY service
        ORDER BY cnt DESC
        LIMIT 200
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Build response
    $resp = [
        'summary' => [
            'total' => $tot,
            'completed' => $completed,
            'pending' => $pending,
            'today' => $todayCount,
            'this_month' => $monthCount,
            'missed' => $missed
        ],
        'appointments_by_date' => $apptsList,
        'gender_counts' => $genderCounts,
        'age_buckets' => $ageBuckets,
        'zone_counts' => $zones,
        'services' => $ss
    ];

    echo json_encode($resp);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error'=>'server','message'=>$e->getMessage()]);
}
