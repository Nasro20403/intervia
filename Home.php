<?php
session_start();
require_once "db.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>INTERVIA | Home</title>
</head>
<body>

<h1>Welcome to Intervia, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
<p>This is your home page.</p>
<a href="messages.php">Messages</a>

</body>
</html>
