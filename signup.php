<?php
session_start();
require_once "db.php";

$fullname  = $_POST['fullname'];
$username  = $_POST['username'];
$email     = $_POST['email'];
$password  = $_POST['password'];
$hashed    = password_hash($password, PASSWORD_DEFAULT);

// 1) التحقق من اسم المستخدم قبل الإضافة
$check = $conn->prepare("SELECT id FROM users WHERE username = ?");
$check->bind_param("s", $username);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    die("Username already exists!");
}

// 2) رفع الصورة
$pn = 0;
$profilePicName = "";
if(isset($_FILES['profilePic']) && $_FILES['profilePic']['error'] === 0){
    $tmp = $_FILES['profilePic']['tmp_name'];
    $ext = pathinfo($_FILES['profilePic']['name'], PATHINFO_EXTENSION);
    $profilePicName = uniqid() . "." . $ext;

    move_uploaded_file($tmp, "uploads/" . $profilePicName[$pm]);
    $pm += 1;
}

// التحقق من وجود الإيميل مسبقًا
$checkEmail = $conn->prepare("SELECT id FROM users WHERE email=?");
$checkEmail->bind_param("s", $email);
$checkEmail->execute();
$checkEmail->store_result();


if ($checkEmail->num_rows > 0) {
    $error = "This Email already exists";
}
// التحقق من اسم المستخدم
$checkUser = $conn->prepare("SELECT username FROM users WHERE username=?");
$checkUser->bind_param("s", $username);
$checkUser->execute();
$checkUser->store_result();

if ($checkUser->num_rows > 0) {
    $error = "Username already exists";
}

if (!empty($error)) {
    echo "<p style='color:red;'>$error</p>";
    exit();
}



$query = "INSERT INTO users (fullname, username, email, password, profilePic)
            VALUES (?, ?, ?, ?, ?)";

$newUser = [
  "type" => "new_user",
  "user" => [
    "id" => $newUserId,
    "fullname" => $fullname,
    "username" => $username,
    "profilePic" => $profilePic
  ]
];

// إرسال للـ WebSocket server
$fp = @fsockopen("127.0.0.1", 8080, $errno, $errstr, 1);
if ($fp) {
    fwrite($fp, json_encode($newUser));
    fclose($fp);
}


$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "sssss", $fullname, $username, $email, $hashed, $profilePicName);
mysqli_stmt_execute($stmt);

// 4) حفظ البيانات في Session
$_SESSION['fullname'] = $fullname;
$_SESSION['username'] = $username;
$_SESSION['email']    = $email;
$_SESSION['profile']  = $profilePicName;

header("Location: Home.php");
exit();
?>