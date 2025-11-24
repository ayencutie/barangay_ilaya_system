<?php
session_start();
require 'db.php'; // Assuming this correctly sets up your $pdo object

// --- 1. INITIAL SECURITY & SETUP ---

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('<div class="error-message">Invalid request method.</div>');
}

// Determine if this is an Admin Override request (for changing OTHER users' passwords)
// Note: This is NOT set by the admin self-change form.
$is_admin_override = isset($_POST['admin_override']) && $_POST['admin_override'] === '1';

// --- 2. AUTHORIZATION GATE: DETERMINE THE TARGET USER ID ---

if ($is_admin_override) {
    // --- ADMIN OVERRIDE PATH (Changing another user's password) ---
    
    // Check if the current user is actually an Admin
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
        exit('<div class="error-message">Access Denied: Admin privileges required.</div>');
    }
    
    // Get the ID of the user whose password the admin wants to change
    $target_patient_id = trim($_POST['target_patient_id'] ?? '');

    if (empty($target_patient_id)) {
        exit('<div class="error-message">Error: Target Patient ID is missing.</div>');
    }
    // Admin bypasses the old password requirement
    $old_password_required = false;

} else {
    // --- STANDARD USER PATH (Self-Service: user changing their own, OR admin changing their own) ---

    // Must be logged in
    if (!isset($_SESSION['patient_id'])) {
        exit('<div class="error-message">Not logged in</div>');
    }
    
    // The user is changing their own password
    $target_patient_id = $_SESSION['patient_id'];
    $old_password_required = true;
}

// --- 3. INPUT GATHERING AND VALIDATION ---

$newpwd = trim($_POST['new_password'] ?? '');
$confirm = trim($_POST['confirm_password'] ?? '');
$oldpwd = trim($_POST['old_password'] ?? ''); // Only required for standard user/admin self-change

if (empty($newpwd) || empty($confirm)) {
    exit('<div class="error-message">New password fields cannot be empty.</div>');
}

if ($newpwd !== $confirm) {
    exit('<div class="error-message">New passwords do not match</div>');
}

// --- 4. CORE LOGIC: OLD PASSWORD VERIFICATION (SKIPPED ONLY FOR ADMIN OVERRIDE) ---

if ($old_password_required) {
    
    // Check old password provided
    if (empty($oldpwd)) {
        exit('<div class="error-message">Current password is required.</div>');
    }

    // Get hashed password from DB
    $stmt = $pdo->prepare("SELECT password FROM users WHERE patient_id = :patient_id");
    $stmt->execute([':patient_id' => $target_patient_id]);
    $user = $stmt->fetch();

    // Verify old password
    if (!$user || !password_verify($oldpwd, $user['password'])) {
        exit('<div class="error-message">Current password is incorrect</div>');
    }
}


// --- 5. CORE LOGIC: UPDATE PASSWORD (REUSED) ---

$hashed = password_hash($newpwd, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("UPDATE users SET password = :password WHERE patient_id = :patient_id");
$stmt->execute([
    ':password' => $hashed,
    ':patient_id' => $target_patient_id
]);

// Final success response
exit('<div class="success-message">Password updated successfully!</div>');
?>