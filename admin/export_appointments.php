<?php
require __DIR__ . '/_auth_admin.php';
require __DIR__ . '/../php/db.php';
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Create new spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set headers
$headers = ['Appointment ID','Patient Name','Service','Date','Time','Status'];
$sheet->fromArray($headers, NULL, 'A1');

// Make headers bold
$sheet->getStyle('A1:F1')->getFont()->setBold(true);

// Fetch appointments
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
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Populate rows
$rowNum = 2;
foreach ($data as $row) {
    $sheet->setCellValue('A'.$rowNum, $row['appointment_id']);
    $sheet->setCellValue('B'.$rowNum, $row['fullname']);
    $sheet->setCellValue('C'.$rowNum, $row['service']);
    
    // Convert date for Excel
    $excelDate = \PhpOffice\PhpSpreadsheet\Shared\Date::stringToExcel($row['date']);
    $sheet->setCellValue('D'.$rowNum, $excelDate);
    $sheet->getStyle('D'.$rowNum)->getNumberFormat()->setFormatCode('DD/MM/YYYY');

    $sheet->setCellValue('E'.$rowNum, $row['time_slot']);
    $sheet->setCellValue('F'.$rowNum, $row['status']);
    $rowNum++;
}

// Auto-size all columns
foreach (range('A','F') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Send Excel to browser
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="appointments.xlsx"');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
