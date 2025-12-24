<?php
session_start();
$host = "localhost";
$user = "Nasro";
$pass = "Nasro2010";
$db = "Nexo";
$conn = mysqli_connect($host, $user, $pass, $db);

if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}

$fullname = $_SESSION['fullname'];
$username = $_SESSION['username'];
$email    = $_SESSION['email'];
$profile  = $_SESSION['profile'];

// عند تعديل البيانات
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $new_fullname = $_POST['fullname'];
    $new_username = $_POST['username'];
    $new_password = $_POST['password'];

    // التحقق من اسم مستخدم مكرر
    $check = $conn->prepare("SELECT id FROM users WHERE username = ? AND username != ?");
    $check->bind_param("ss", $new_username, $username);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        die("Username already taken!");
    }

    // تعديل كلمة السر
    if (!empty($new_password)) {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $pass_query = ", password='$hashed'";
    } else {
        $pass_query = "";
    }

    // تعديل الصورة
    if(isset($_FILES['profile']) && $_FILES['profile']['error'] === 0){
        $tmp = $_FILES['profile']['tmp_name'];
        $ext = pathinfo($_FILES['profile']['name'], PATHINFO_EXTENSION);
        $newPic = uniqid().".".$ext;
        move_uploaded_file($tmp, "uploads/".$newPic);
    } else {
        $newPic = $profile;
    }

    // تحديث قاعدة البيانات
    $update = "UPDATE users SET fullname='$new_fullname', username='$new_username', profilePic='$newPic' $pass_query 
               WHERE username='$username'";
    mysqli_query($conn, $update);

    // تحديث السيشن
    $_SESSION['fullname'] = $new_fullname;
    $_SESSION['username'] = $new_username;
    $_SESSION['profile']  = $newPic;

    header("Location: Home.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Profile</title>
<style>
body {
    background: #111;
    color: white;
    font-family: Arial;
}
.box {
    width: 350px;
    margin: 40px auto;
    padding: 20px;
    background: #1a1a1a;
    border-radius: 12px;
    text-align: center;
}
input {
    width: 90%;
    padding: 10px;
    margin: 10px 0;
    border-radius: 8px;
    border: none;
}
button {
    padding: 10px;
    width: 90%;
    background: #0a84ff;
    color: white;
    border: none;
    border-radius: 8px;
}
button:hover {
    background: #1b95ff;
}
a {
    color: #ccc;
    text-decoration: none;
}
</style>
</head>

<body>
<div class="box">
    <h2>Edit Profile</h2>

    <form action="" method="POST" enctype="multipart/form-data">

        <img src="uploads/<?= $profile ?>" width="120" height="120" style="border-radius:50%;border:3px solid #444;">

        <input type="file" name="profile">

        <input type="text" name="fullname" value="<?= $fullname ?>" placeholder="Full Name" required>
        <input type="text" name="username" value="<?= $username ?>" placeholder="Username" required>
        <input type="password" name="password" placeholder="New Password (optional)">

        <button type="submit">Save Changes</button>
    </form>

    <br>

    <a href="Home.php">Back</a>
</div>
</body>
</html>
