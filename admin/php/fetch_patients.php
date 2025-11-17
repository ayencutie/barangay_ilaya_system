<?php
require 'db.php';

$res = $conn->query("
    SELECT patient_id, first_name, last_name 
    FROM users
    WHERE user_role='patient'
");

$list = [];
while($r = $res->fetch_assoc()) $list[] = $r;

echo json_encode($list);
?>
