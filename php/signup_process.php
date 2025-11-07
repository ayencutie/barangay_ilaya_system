<?php
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $phone = $_POST['phone'];

    // Generate auto Account ID (e.g., 0001, 0002, ...)
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $row = $result->fetch_assoc();
    $account_id = str_pad($row['count'] + 1, 4, '0', STR_PAD_LEFT);

    $sql = "INSERT INTO users (account_id, fullname, email, password, phone) 
            VALUES ('$account_id', '$fullname', '$email', '$password', '$phone')";
    
    if ($conn->query($sql) === TRUE) {
        header("Location: ../login.html?success=1");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}
?>
