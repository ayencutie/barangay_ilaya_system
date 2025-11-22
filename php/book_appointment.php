<?php
session_start();
// Use the recommended PDO connection pattern
require 'db.php'; 

// Set Content-Type for consistent error handling (though currently using <script> alert)
header('Content-Type: text/html'); 

// --- 1. User & Session Validation (Retained) ---
if(!isset($_SESSION['patient_id'])){
    // Using simple header redirect for a clean exit
    header("Location: ../landing_page.html"); 
    exit("You must be logged in to book an appointment.");
}

$patient_id = $_SESSION['patient_id'];

// Normalize and check user role
$user_role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? null;
if (($user_role ?? '') !== 'patient') {
    // If not a patient, send them away
    header("Location: ../landing_page.html"); 
    exit('Only patient accounts are allowed to book appointments.');
}
// --- End User Validation ---

if($_SERVER["REQUEST_METHOD"] === "POST"){
    $service = trim($_POST['service']);
    $date = trim($_POST['date']);
    $time_slot = trim($_POST['time_slot']);

    // Input Sanitization Check
    if (empty($service) || empty($date) || empty($time_slot)) {
        echo "<script>alert('All fields are required.'); window.history.back();</script>";
        exit;
    }

    // --- 2. Future Date/Time Check (Retained) ---
    // (Your existing logic for checking past time is kept here)
    // ... (Your date/time validation code goes here) ...
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
    // --- End Date/Time Check ---

    try {
        // --- 3. CRITICAL: Start Atomic Transaction ---
        $pdo->beginTransaction();

        // 3a. CONCURRENCY CHECK with Pessimistic Lock
        // Check if any appointment for this specific service/date/time_slot exists.
        // FOR UPDATE locks any matching rows, preventing other users from booking it 
        // until the current transaction COMMITs or ROLLBACKs.
        $check_sql = "SELECT COUNT(*) FROM appointments 
                      WHERE service = :service AND date = :date AND time_slot = :time_slot 
                      FOR UPDATE"; 
                      
        $stmt_check = $pdo->prepare($check_sql);
        $stmt_check->execute([
            ':service' => $service,
            ':date' => $date,
            ':time_slot' => $time_slot
        ]);
        $count = $stmt_check->fetchColumn();

        if ($count > 0) {
            // Slot is already taken (either by a previous successful request 
            // or a concurrent one that got to the SELECT first).
            $pdo->rollBack(); 
            // Send an alert to the user.
            echo "<script>alert('❌ Booking Failed: That exact slot has just been booked. Please select another time or refresh the page.'); window.history.back();</script>";
            exit;
        }

        // 3b. INSERT the Appointment
        // This only executes if the COUNT was 0 and the lock was successfully acquired.
        $stmt_insert = $pdo->prepare("
            INSERT INTO appointments (patient_id, service, date, time_slot, status)
            VALUES (:patient_id, :service, :date, :time_slot, 'Pending')
        ");
        $stmt_insert->execute([
            ':patient_id' => $patient_id,
            ':service' => $service,
            ':date' => $date,
            ':time_slot' => $time_slot
        ]);

        // 3c. Commit the transaction and release the lock
        $pdo->commit(); 

        // SUCCESS
        echo "<script>
                alert('✅ Appointment booked successfully and pending approval!');
                window.location='../my_appointment.html';
              </script>";
        
    } catch(PDOException $e){
        // 4. Handle Errors (Rollback if something went wrong)
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        // Check for integrity constraint violation (SQLSTATE 23000)
        // This is a backup check in case your table has a UNIQUE index (recommended).
        if ($e->getCode() === '23000') {
            $errorMessage = "❌ Booking Failed: This slot is already reserved (Database Integrity Error).";
        } else {
            $errorMessage = "❌ An unexpected error occurred: Please try again.";
        }

        error_log("Appointment Booking Error: " . $e->getMessage());
        echo "<script>alert('".$errorMessage."'); window.history.back();</script>";
    }
} else {
    // Not a POST request
    header("Location: ../book appointment.html");
    exit;
}
?>