<?php
session_start();
require 'db.php';

if(!isset($_SESSION['patient_id'])){
    die("You must be logged in to book an appointment.");
}

$patient_id = $_SESSION['patient_id'];

// Ensure only patients can book appointments.
// Some pages use `role` while others use `user_role` in session — normalize.
$user_role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? null;
if (!$user_role) {
    // Try to fetch role from DB as a fallback
    try {
        $rstmt = $pdo->prepare("SELECT user_role FROM users WHERE patient_id = ? LIMIT 1");
        $rstmt->execute([$patient_id]);
        $row = $rstmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['user_role'])) {
            $user_role = $row['user_role'];
            $_SESSION['user_role'] = $user_role;
            $_SESSION['role'] = $user_role;
        }
    } catch (Exception $e) {
        // ignore DB errors here; we'll enforce role check below
    }
}

if (($user_role ?? '') !== 'patient') {
    die('Only patient accounts are allowed to book appointments.');
}

if($_SERVER["REQUEST_METHOD"] === "POST"){
    $service = $_POST['service'];
    $date = $_POST['date'];
    $time_slot = $_POST['time_slot'];

    // --- Server-side: prevent booking a past date/time ---
    $startPart = '';
    if (strpos($time_slot, '-') !== false) {
        $parts = explode('-', $time_slot);
        $startPart = trim($parts[0]);
    } else {
        $startPart = trim($time_slot);
    }

    $bookingDateTime = false;
    if ($startPart !== '') {
        $dt = DateTime::createFromFormat('Y-m-d g:i A', $date . ' ' . $startPart);
        if ($dt !== false) {
            $bookingDateTime = $dt;
        } else {
            $ts = strtotime($date . ' ' . $startPart);
            if ($ts !== false) $bookingDateTime = (new DateTime())->setTimestamp($ts);
        }
    }

    if ($bookingDateTime === false) {
        echo "<script>alert('Invalid date or time selected.'); window.history.back();</script>";
        exit;
    }

    $now = new DateTime();
    if ($bookingDateTime <= $now) {
        echo "<script>alert('Cannot book a time in the past. Please choose a future date/time.'); window.history.back();</script>";
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO appointments (patient_id, service, date, time_slot)
            VALUES (:patient_id, :service, :date, :time_slot)
        ");
        $stmt->execute([
            ':patient_id' => $patient_id,
            ':service' => $service,
            ':date' => $date,
            ':time_slot' => $time_slot
        ]);

        echo "<script>
                alert('Appointment booked successfully!');
                window.location='../my_appointment.html';
              </script>";
    } catch(PDOException $e){
        die("Error: ".$e->getMessage());
    }
}
?>
