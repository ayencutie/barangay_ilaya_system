<?php
session_start();
require 'db.php'; // ✅ tama na connection file

// ✅ Check kung naka-login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_id   = $_SESSION['user_id'];
    $service   = trim($_POST['service'] ?? '');
    $date      = trim($_POST['date'] ?? '');
    $time_slot = trim($_POST['time_slot'] ?? '');

    // ✅ Basic input validation
    if (empty($service) || empty($date) || empty($time_slot)) {
        echo "<script>alert('Please complete all fields.'); window.history.back();</script>";
        exit;
    }

    // ✅ Validate date format (YYYY-MM-DD)
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        echo "<script>alert('Invalid date format.'); window.history.back();</script>";
        exit;
    }

    // ✅ Optional: prevent past bookings
    $today = date('Y-m-d');
    if ($date < $today) {
        echo "<script>alert('You cannot book an appointment in the past.'); window.history.back();</script>";
        exit;
    }

    try {
        // ✅ Check kung may kaparehong booking (optional)
        $check = $pdo->prepare("
            SELECT COUNT(*) FROM appointments 
            WHERE user_id = :user_id AND date = :date AND time_slot = :time_slot
        ");
        $check->execute([
            ':user_id' => $user_id,
            ':date' => $date,
            ':time_slot' => $time_slot
        ]);
        $exists = $check->fetchColumn();

        if ($exists > 0) {
            echo "<script>alert('You already booked this time slot.'); window.history.back();</script>";
            exit;
        }

        // ✅ Insert booking
        $stmt = $pdo->prepare("
            INSERT INTO appointments (user_id, service, date, time_slot, status)
            VALUES (:user_id, :service, :date, :time_slot, 'Pending')
        ");
        $stmt->execute([
            ':user_id'   => $user_id,
            ':service'   => $service,
            ':date'      => $date,
            ':time_slot' => $time_slot
        ]);

        echo "<script>alert('Appointment booked successfully!'); window.location.href='../my_appointment.html';</script>";
        exit;

    } catch (PDOException $e) {
        // ✅ Error handling
        error_log('Database Error: ' . $e->getMessage());
        echo "<script>alert('An error occurred while booking. Please try again later.'); window.history.back();</script>";
        exit;
    }
}
?>
