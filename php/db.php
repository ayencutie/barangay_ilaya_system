<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "barangay_ilaya"; // make sure this matches your phpMyAdmin database name

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
