<?php
require __DIR__ . '/_auth_admin.php';
require __DIR__ . '/../php/db.php';

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="appointments.csv"');

// Open output stream
$output = fopen('php://output', 'w');

// CSV headers
$headers = ['Appointment ID','Patient Name','Service','Date','Time','Status'];
fputcsv($output, $headers);

// Fetch appointments from database
$sql = "SELECT 
            a.appointment_id,
            CONCAT(u.first_name, ' ', u.middle_initial, '. ', u.last_name) AS fullname,
            a.service,
            a.`date`,
            a.time_slot,
            a.status
        FROM appointments a
        JOIN users u ON a.patient_id = u.patient_id
        ORDER BY a.`date` DESC, a.time_slot DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute();

// Output rows
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    // Force Excel to treat date properly
    $excelDate = '="' . date('Y-m-d', strtotime($row['date'])) . '"';

    // Add padding tabs to avoid data being squeezed (for professional look)
    $patientName = $row['fullname'] . "\t";
    $service = $row['service'] . "\t";
    $timeSlot = $row['time_slot'] . "\t";
    $status = $row['status'] . "\t";

    fputcsv($output, [
        $row['appointment_id'],
        $patientName,
        $service,
        $excelDate,
        $timeSlot,
        $status
    ]);
}

fclose($output);
exit;
