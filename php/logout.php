<?php
session_start();
session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Logging out...</title>
<link rel="stylesheet" href="../css/loading.css">
</head>
<body>
<div class="loader-container">
  <div class="spinner"></div>
  <p>Logging out...</p>
</div>

<script>
// redirect after 1.5 seconds (adjust as needed)
setTimeout(function(){
    window.location.href = "../landing_page.html";
}, 1500);
</script>
</body>
</html>
