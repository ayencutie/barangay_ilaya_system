<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../db.php';  // FIXED PATH (admin → root)


// --------------------------------------------
// 1. CHECK LOGIN
// --------------------------------------------
if (!isset($_SESSION['patient_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Not authenticated'
    ]);
    exit;
}

$pid = $_SESSION['patient_id'];


// --------------------------------------------
// 2. GET USER DATA
// --------------------------------------------
$stmt = $pdo->prepare("SELECT patient_id, first_name, last_name, user_role, email 
                       FROM users WHERE patient_id = ? LIMIT 1");
$stmt->execute([$pid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['status'=>'error','message'=>'User not found']);
    exit;
}


// --------------------------------------------
// 3. CHECK ADMIN ROLE
// --------------------------------------------
$isAdmin = false;

// A. Main admin role
if ($user['user_role'] === 'admin') $isAdmin = true;

// B. Backup: email fixed admin
if (strtolower($user['email']) === 'admin@healthcenter.com') $isAdmin = true;

// C. Backup: patient_id starts with ADM
if (preg_match('/^ADM/i', $user['patient_id'])) $isAdmin = true;

if (!$isAdmin) {
    echo json_encode(['status'=>'error','message'=>'Not authorized']);
    exit;
}


// --------------------------------------------
// 4. GET APPOINTMENTS LIST
// --------------------------------------------
try {
    $sql = "
        SELECT a.appointment_id, a.patient_id, a.service, a.date, a.time_slot, a.status,
               u.first_name, u.last_name
        FROM appointments a
        LEFT JOIN users u ON u.patient_id = a.patient_id
        ORDER BY a.date DESC, a.time_slot DESC
    ";

    $stmt = $pdo->query($sql);

    $appointments = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $appointments[] = [
            'appointment_id' => $row['appointment_id'],
            'patient_id'     => $row['patient_id'],
            'patient_name'   => trim($row['first_name'] . ' ' . $row['last_name']),
            'service'        => $row['service'],
            'date'           => $row['date'],
            'time_slot'      => $row['time_slot'],
            'status'         => $row['status']
        ];
    }

    echo json_encode([
        'status' => 'success',
        'appointments' => $appointments
    ]);
    exit;

} catch (PDOException $e) {

    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    exit;
}
?>
