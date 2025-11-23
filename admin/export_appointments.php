<?php
require __DIR__ . '/_auth_admin.php';
require __DIR__ . '/../php/db.php';

// 1. Palitan ang headers para maging Excel file (.xls)
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="appointments.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// Fetch appointments (Yung dating query mo)
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

// 2. Gumawa ng HTML Table (Dito tayo magse-set ng lapad/width)
// Ang 'border=1' ay para may linya ang cells sa Excel
echo '<table border="1">';

// TABLE HEADERS
echo '<tr>';
// Pansinin ang 'style="width: ... px"'. Dito mo icocontrol kung gaano kalapad.
echo '<th style="width: 150px; background-color: #YELLOW;">Appointment ID</th>';
echo '<th style="width: 300px; background-color: #YELLOW;">Patient Name</th>'; // Mas malapad para sa pangalan
echo '<th style="width: 200px; background-color: #YELLOW;">Service</th>';
echo '<th style="width: 120px; background-color: #YELLOW;">Date</th>';
echo '<th style="width: 120px; background-color: #YELLOW;">Time</th>';
echo '<th style="width: 150px; background-color: #YELLOW;">Status</th>';
echo '</tr>';

// 3. I-loop ang data at ilagay sa rows
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo '<tr>';
    
    // Appointment ID (Gitna natin para malinis)
    echo '<td style="text-align:center;">' . $row['appointment_id'] . '</td>';
    
    // Name
    echo '<td>' . $row['fullname'] . '</td>';
    
    // Service
    echo '<td>' . $row['service'] . '</td>';
    
    // Date
    echo '<td>' . date('Y-m-d', strtotime($row['date'])) . '</td>';
    
    // Time
    echo '<td>' . $row['time_slot'] . '</td>';
    
    // Status
    echo '<td>' . $row['status'] . '</td>';
    
    echo '</tr>';
}

echo '</table>';
exit;
?>