<?php
session_start();
require 'php/db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id=:id");
$stmt->execute([':id'=>$user_id]);
$user = $stmt->fetch();
if(!$user){
    exit("User not found");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Account Settings – Barangay Ilaya Health Center</title>
<link rel="stylesheet" href="css/account.css">
</head>
<body>

<header class="navbar">
  <div class="logo">
    <img src="https://i.ibb.co/qFPvFtms/167565708-10159274632132790-1386372340681598199-n-removebg-preview.png" alt="Logo">
    <h1>BARANGAY ILAYA HEALTH CENTER</h1>
  </div>
  <nav>
    <ul>
      <li><a href="index.html">HOME</a></li>
      <li><a href="new about us.html">ABOUT US</a></li>
      <li><a href="new services.html">SERVICES</a></li>
      <li class="dropdown">
        <button class="dropbtn" id="menuButton">MENU ▼</button>
        <div class="dropdown-content" id="dropdownMenu">
          <a href="book appointment.html">Book Appointment</a>
          <a href="my_appointment.html">My Appointment</a>
          <a href="inbox.html">Inbox</a>
          <a href="account.php">Account</a>
        </div>
      </li>
      <li><a href="php/logout.php" class="logout-btn">LOG OUT</a></li>
    </ul>
  </nav>
</header>

<main class="content">
  <div class="account-card form-card">
    <h2>Account Settings</h2>

    <!-- Messages -->
    <div id="messages"></div>

    <div class="profile-list">
      <div class="profile-item"><label>Account ID</label><div id="acct"><?= htmlspecialchars($user['account_id']); ?></div></div>
      <div class="profile-item"><label>Full Name</label><div id="name"><?= htmlspecialchars($user['first_name'].' '.$user['last_name']); ?></div></div>
      <div class="profile-item"><label>Address</label><div id="address"><?= htmlspecialchars($user['address']); ?></div></div>
      <div class="profile-item"><label>Email</label><div id="email"><?= htmlspecialchars($user['email']); ?></div></div>
      <div class="profile-item"><label>Phone</label><div id="phone"><?= htmlspecialchars($user['phone']); ?></div></div>
    </div>

    <div class="action-buttons">
      <button id="chgPhone" class="btn">Change Phone</button>
      <button id="chgPassword" class="btn">Change Password</button>
    </div>

    <!-- Password Form -->
    <form id="pwdForm" class="password-form" style="display:none;">
      <h3>Change Password</h3>
      <input type="password" id="oldpwd" placeholder="Old password" required>
      <input type="password" id="newpwd" placeholder="New password" required>
      <input type="password" id="confirmpwd" placeholder="Confirm new password" required>
      <button type="submit" class="btn">Save Password</button>
    </form>
  </div>
</main>

<!-- Phone Modal -->
<div id="phoneModal" class="modal">
  <div class="modal-content">
    <h4>Change Phone Number</h4>
    <input id="newPhone" placeholder="11-digit phone" maxlength="11">
    <input id="pwdForPhone" type="password" placeholder="Enter your password">
    <div class="modal-actions">
      <button id="savePhone" class="btn">Save</button>
      <button id="closePhone" class="btn alt">Cancel</button>
    </div>
  </div>
</div>

<script>
// Dropdown Menu
const menuButton = document.getElementById('menuButton');
const dropdownMenu = document.getElementById('dropdownMenu');
menuButton.addEventListener('click', () => dropdownMenu.classList.toggle('show'));
window.addEventListener('click', e => {
  if(!menuButton.contains(e.target) && !dropdownMenu.contains(e.target)) dropdownMenu.classList.remove('show');
});

// Show Password Form
const pwdForm = document.getElementById('pwdForm');
document.getElementById('chgPassword').addEventListener('click', () => {
  pwdForm.style.display = pwdForm.style.display === 'none' ? 'flex' : 'none';
  pwdForm.scrollIntoView({behavior:'smooth'});
});

// Phone Modal
document.getElementById('chgPhone').addEventListener('click', ()=>document.getElementById('phoneModal').style.display='flex');
document.getElementById('closePhone').addEventListener('click', ()=>document.getElementById('phoneModal').style.display='none');

// AJAX: Update Phone
document.getElementById('savePhone').addEventListener('click', ()=>{
    const phone = document.getElementById('newPhone').value;
    const pwd = document.getElementById('pwdForPhone').value;
    const msgDiv = document.getElementById('messages');

    if(!/^\d{11}$/.test(phone)){ alert('Enter 11-digit phone number'); return; }

    fetch('php/update_phone.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `phone=${encodeURIComponent(phone)}&password=${encodeURIComponent(pwd)}`
    })
    .then(res=>res.text())
    .then(data=>{
        msgDiv.innerHTML = data;
        if(data.includes('success')){
            document.getElementById('phone').textContent = phone;
            document.getElementById('phoneModal').style.display='none';
        }
    });
});

// AJAX: Update Password
pwdForm.addEventListener('submit', e=>{
    e.preventDefault();
    const oldpwd = document.getElementById('oldpwd').value;
    const newpwd = document.getElementById('newpwd').value;
    const confirmpwd = document.getElementById('confirmpwd').value;
    const msgDiv = document.getElementById('messages');

    if(newpwd !== confirmpwd){ alert('Passwords do not match'); return; }

    fetch('php/update_password.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:`old_password=${encodeURIComponent(oldpwd)}&new_password=${encodeURIComponent(newpwd)}&confirm_password=${encodeURIComponent(confirmpwd)}`
    })
    .then(res=>res.text())
    .then(data=>{
        msgDiv.innerHTML = data;
        if(data.includes('success')){
            pwdForm.reset();
            pwdForm.style.display='none';
        }
    });
});
</script>
</body>
</html>
