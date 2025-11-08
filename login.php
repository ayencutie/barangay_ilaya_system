<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Health Center | Login</title>
  <link rel="stylesheet" href="css/login.css" />
</head>
<body>
  <div class="container">
    <div class="logo">
      <img src="https://i.ibb.co/zVbCpfct/167565708-10159274632132790-1386372340681598199-n-removebg-preview.png" alt="Health Center Logo" />
    </div>

    <h2>Health Center Login</h2>

    <!-- Display error message -->
    <?php
      if (isset($_SESSION['login_error'])) {
          echo "<div class='error-message'>" . htmlspecialchars($_SESSION['login_error']) . "</div>";
          unset($_SESSION['login_error']);
      }
    ?>

    <form action="php/login_process.php" method="post">
      <input type="email" name="email" placeholder="Email" required />
      <input type="password" name="password" placeholder="Password" required />
      <button type="submit" class="button-link">Log In</button>
    </form>

    <p>Don't have an account? <a href="signup.html">Create one</a></p>
  </div>
</body>
</html>
