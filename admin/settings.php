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

    <!-- SIDEBAR — SAME AS DASHBOARD -->
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

    <!-- MAIN CONTENT WRAPPER -->
    <main class="main">

        <!-- TOP NAVBAR HEADER -->
        <div class="header">
            <h1>Settings</h1>
            <input type="text" placeholder="Search here..." class="search">
        </div>

        <!-- PAGE CONTENT -->
        <div class="settings-wrapper">

            <!-- PROFILE -->
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

                <!-- DATE REGISTERED REMOVED -->
            </div>

            <!-- CHANGE PASSWORD -->
            <div class="card">
                <h2 class="card-title">Change Password</h2>

                <form id="changePassForm">
                    <div class="input-group">
                        <label>Current Password</label>
                        <input type="password" id="currentPass" required>
                    </div>

                    <div class="input-group">
                        <label>New Password</label>
                        <input type="password" id="newPass" required>
                    </div>

                    <button type="submit" class="btn">Update Password</button>
                </form>

                <p id="passMessage"></p>
            </div>

        </div>
    </main>

    <!-- CLEAN JS (NO DARK MODE) -->
    <script>
    document.addEventListener("DOMContentLoaded", () => {
        const form = document.getElementById("changePassForm");

        form.addEventListener("submit", e => {
            e.preventDefault();

            let cur = document.getElementById("currentPass").value;
            let newp = document.getElementById("newPass").value;

            fetch("update_password.php", {
                method: "POST",
                headers: {"Content-Type": "application/x-www-form-urlencoded"},
                body: `current=${cur}&new=${newp}`
            })
            .then(r => r.text())
            .then(msg => {
                document.getElementById("passMessage").textContent = msg;
            });
        });
    });
    </script>

</body>
</html>
