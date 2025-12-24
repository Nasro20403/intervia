<?php
session_start();
include "db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // استعلام التحقق
    $sql = "SELECT * FROM users WHERE username=? OR email=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {

        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {

            // تخزين بيانات المستخدم في السيشن
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email']    = $user['email'];
            $_SESSION['profile']  = $user['profilepic'];

            // ⬅ توجيه المستخدم مباشرة إلى غرفته الخاصة في الرسائل
            header("Location: messages.php?user_id=" . $user['id']);
            exit();

        } else {
            $error = "User-name or password is not correct";
        }

    } else {
        $error = "User-name or password is not correct";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login | Nexo</title>

<style>
body {
    background: #111;
    color: white;
    font-family: Arial;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}

.login-box {
    background: #1a1a1a;
    padding: 30px;
    width: 350px;
    border-radius: 12px;
    box-shadow: 0 0 15px black;
    text-align: center;
}

input {
    width: 90%;
    padding: 12px;
    margin: 10px 0;
    border: none;
    outline: none;
    border-radius: 8px;
    background: #333;
    color: white;
}

button {
    width: 95%;
    padding: 12px;
    background: #0a84ff;
    border: none;
    border-radius: 8px;
    color: white;
    cursor: pointer;
    margin-top: 10px;
}

button:hover {
    background: #1b95ff;
}

.error {
    color: #ff4d4d;
    font-size: 14px;
    margin: -5px 0 5px;
}
</style>

</head>

<body>

<div class="login-box">
    <h2>Login</h2>

    <form action="" method="POST">

        <input type="text" name="username" placeholder="Username or Email" required>

        <?php if ($error != ""): ?>
            <p class="error"><?= $error ?></p>
        <?php endif; ?>

        <input type="password" name="password" placeholder="Password" required>

        <button type="submit">Login</button>
    </form>
</div>

</body>
</html>
