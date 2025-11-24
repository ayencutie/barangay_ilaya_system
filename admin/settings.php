<?php
require __DIR__ . '/_auth_admin.php';
require __DIR__ . '/../php/db.php';

$adminID = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE patient_id = ?");
$stmt->execute([$adminID]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings — Barangay Ilaya</title>


    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/settings.css">
</head>

<body>

    <aside class="sidebar">
        <h2>Barangay Ilaya</h2>
        <ul class="nav">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="appointments.php">Appointments</a></li>
            <li><a href="patients.php">Patients</a></li>
            <li><a href="admin_inbox.php">Inbox</a></li>
            <li><a href="reports.php">Reports & Analytics</a></li>
            <li><a href="settings.php" class="active">Settings</a></li>
            <li><a href="../php/logout.php">Logout</a></li>
        </ul>
    </aside>

    <main class="main">

        <div class="header">
            <h1>Settings</h1>
            <input type="text" placeholder="Search here..." class="search">
        </div>

        <div class="settings-wrapper">

            <div class="card">
                <h2 class="card-title">Profile Information</h2>

                <div class="info-row">
                    <label>Admin ID:</label>
                    <span><?= $user['patient_id']; ?></span>
                </div>

                <div class="info-row">
                    <label>Full Name:</label>
                    <span><?= $user['first_name'] . " " . $user['last_name']; ?></span>
                </div>

                <div class="info-row">
                    <label>Email:</label>
                    <span><?= $user['email']; ?></span>
                </div>

                </div>

            <div class="card">
                <h2 class="card-title">Change Password</h2>

                <form id="changePassForm">
                    <div class="input-group">
                        <label>Current Password</label>
                        <input type="password" id="currentPass" name="old_password" required>
                    </div>

                    <div class="input-group">
                        <label>New Password</label>
                        <input type="password" id="newPass" name="new_password" required>
                    </div>
                    
                    <div class="input-group">
                        <label>Confirm New Password</label>
                        <input type="password" id="confirmNewPass" name="confirm_password" required>
                    </div>

                    <button type="submit" class="btn">Update Password</button>
                </form>

                <p id="passMessage"></p>
            </div>

        </div>
    </main>

    <script>
    document.addEventListener("DOMContentLoaded", () => {
        const form = document.getElementById("changePassForm");
        const msgP = document.getElementById("passMessage");

        form.addEventListener("submit", e => {
            e.preventDefault();
            
            // Client-Side Validation: Check if new passwords match
            let newP = document.getElementById("newPass").value;
            let confirmP = document.getElementById("confirmNewPass").value;

            if (newP !== confirmP) {
                msgP.innerHTML = '<span style="color:red;">New passwords do not match.</span>';
                return;
            }

            // Prepare FormData (automatically grabs all named inputs)
            const formData = new FormData(form);

            // FIX 1: Corrected the fetch path to navigate out of the admin folder (..)
            // FIX 2: Using FormData instead of manual body construction
            fetch("../php/update_password.php", {
                method: "POST",
                body: formData
            })
            .then(r => r.text())
            .then(msg => {
                // Display the success/error message from the PHP script
                msgP.innerHTML = msg; 
                
                // Clear the form fields if the update was successful
                if (msg.includes('success-message')) {
                    form.reset();
                }
            })
            .catch(err => {
                msgP.innerHTML = '<span style="color:red;">Network error or failed to connect.</span>';
            });
        });
    });
    </script>

</body>
</html>