<?php
require 'db.php';

try {
    $stmt = $pdo->query("SELECT DATABASE()");
    $dbName = $stmt->fetchColumn();
    echo "✅ Connected successfully to database: " . $dbName;
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage();
}
?>
