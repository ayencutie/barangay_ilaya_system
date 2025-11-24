<?php
session_start();
// Assuming db.php correctly establishes a PDO connection named $pdo
require 'db.php';

// ------------------------------------------------------------
// Timezone (ensure correct comparison for PH time)
// ------------------------------------------------------------
date_default_timezone_set('Asia/Manila');

// Ensure consistent output type
header('Content-Type: text/html');

// ------------------------------------------------------------
// USER VALIDATION
// ------------------------------------------------------------
if (!isset($_SESSION['patient_id'])) {
    header("Location: ../landing_page.html");
    exit("You must be logged in to book an appointment.");
}

$patient_id = $_SESSION['patient_id'];
$user_role  = $_SESSION['user_role'] ?? $_SESSION['role'] ?? null;

if ($user_role !== 'patient') {
    header("Location: ../landing_page.html");
    exit("Only patient accounts can book appointments.");
}

// ------------------------------------------------------------
// ONLY ACCEPT POST REQUESTS
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../book appointment.html");
    exit;
}

// ------------------------------------------------------------
// INPUTS + BASIC VALIDATION
// ------------------------------------------------------------
$service    = trim($_POST['service'] ?? '');
$date       = trim($_POST['date'] ?? '');
$time_slot  = trim($_POST['time_slot'] ?? '');

if ($service === '' || $date === '' || $time_slot === '') {
    echo "<script>alert('All fields are required.'); window.history.back();</script>";
    exit;
}

// ------------------------------------------------------------
// WEEKEND VALIDATION
// ------------------------------------------------------------
$timestamp = strtotime($date);
$dayOfWeek = date('N', $timestamp); // 1=Monday, 7=Sunday

if ($dayOfWeek >= 6) { // 6 = Saturday, 7 = Sunday
    echo "<script>
            alert('❌ Appointments cannot be booked on Saturdays or Sundays. Please choose a weekday.');
            window.history.back();
          </script>";
    exit;
}


// ------------------------------------------------------------
// FUTURE DATE/TIME VALIDATION
// ------------------------------------------------------------

// Extract **end time** of slot (ex: "8:30 AM - 8:40 AM" → "8:40 AM")
$time_parts      = explode(' - ', $time_slot);
$end_time_string = trim(end($time_parts));

// Combine date and end time
$datetime_string = $date . ' ' . $end_time_string;

// Expected format
$format = 'Y-m-d g:i A';

// Create DateTime object
$appointment_end = DateTime::createFromFormat($format, $datetime_string);

if (!$appointment_end) {
    // fallback using strtotime
    $timestamp = strtotime($datetime_string);
    if ($timestamp !== false) {
        $appointment_end = (new DateTime())->setTimestamp($timestamp);
    } else {
        echo "<script>alert('Invalid date or time selected.'); window.history.back();</script>";
        exit;
    }
}

$now = new DateTime();

// Reject if end time is in the past or same as now
if ($appointment_end <= $now) {
    echo "<script>alert('❌ Cannot book a time that has already passed. Please choose a future slot.'); window.history.back();</script>";
    exit;
}

// ------------------------------------------------------------
// DATABASE TRANSACTION (Atomic)
// ------------------------------------------------------------
try {
    $pdo->beginTransaction();

    // --------------------------------------------------------
    // Check if the slot is still available (lock row)
    // FIX: Removed ':service' from WHERE clause to prevent double booking 
    //      on the same time slot, regardless of the service.
    // --------------------------------------------------------
    $sql_check = "
        SELECT COUNT(*) 
        FROM appointments 
        WHERE date = :date AND time_slot = :time_slot
        FOR UPDATE
    ";

    $stmt = $pdo->prepare($sql_check);
    $stmt->execute([
        ':date'      => $date,
        ':time_slot' => $time_slot
    ]);

    if ($stmt->fetchColumn() > 0) {
        $pdo->rollBack();
        echo "<script>alert('❌ This time slot is already taken by another appointment. Please choose another.'); window.history.back();</script>";
        exit;
    }

    // --------------------------------------------------------
    // INSERT NEW APPOINTMENT
    // --------------------------------------------------------
    $sql_insert = "
        INSERT INTO appointments (patient_id, service, date, time_slot, status)
        VALUES (:patient_id, :service, :date, :time_slot, 'Pending')
    ";

    $stmt = $pdo->prepare($sql_insert);
    $stmt->execute([
        ':patient_id' => $patient_id,
        ':service'    => $service,
        ':date'       => $date,
        ':time_slot'  => $time_slot
    ]);

    $pdo->commit();

    // SUCCESS
    echo "<script>
            alert('✅ Appointment booked successfully and pending approval!');
            window.location='../my_appointment.html';
          </script>";

} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Note: The Integrity Error (23000) might occur if you also set a UNIQUE index on (date, time_slot) in the database.
    $msg = ($e->getCode() === '23000')
        ? "❌ A conflict occurred. The time slot might have been taken just now. (Integrity Error)"
        : "❌ Unexpected error occurred. Please try again.";

    error_log("Appointment Booking Error: " . $e->getMessage());

    echo "<script>alert('$msg'); window.history.back();</script>";
}
?>