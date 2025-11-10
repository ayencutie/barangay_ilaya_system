<?php
// Start session at the very top
session_start();

// Optional: Check if the user is an admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    // Redirect non-admin users to landing page
    header("Location: ../landing_page.html");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard – Barangay Ilaya Health Center</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>

<!-- Include the admin navbar -->
<?php include 'includes/admin_nav.php'; ?>

<main class="admin-content">
    <h1>Welcome to Admin Dashboard</h1>
    <p>Here you can manage appointments, users, and messages.</p>

    <!-- Example: Quick stats -->
    <div class="stats">
        <div class="card">
            <h3>Total Users</h3>
            <p>150</p>
        </div>
        <div class="card">
            <h3>Appointments Today</h3>
            <p>12</p>
        </div>
        <div class="card">
            <h3>Unread Messages</h3>
            <p>3</p>
        </div>
    </div>
</main>

</body>
</html>
