

<?php
session_start();

session_start();
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

// إذا المستخدم لم يسجل دخول
if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}


$fullname = $_SESSION['fullname'];
$username = $_SESSION['username'];
$email    = $_SESSION['email'];
$profile  = $_SESSION['profile'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Welcome | Nexo</title>
<link rel="stylesheet" href="home.css">
<style>
/* زر الهامبرغر */
.hamburger {
    width: 22px;
    cursor: pointer;
    margin-right: 10px;
    transition: 0.3s;
}

.hamburger span {
    display: block;
    width: 100%;
    height: 3px;
    background: white;
    margin: 4px 0;
    border-radius: 5px;
    transition: 0.4s;
}

/* عند تفعيل القائمة يصبح X */
.hamburger.active span:nth-child(1) {
    transform: translateY(7px) rotate(45deg);
}
.hamburger.active span:nth-child(2) {
    opacity: 0;
}
.hamburger.active span:nth-child(3) {
    transform: translateY(-7px) rotate(-45deg);
}


body {
    margin: 0;
    font-family: Arial;
    background: #111;
    color: white;
}
.container {
    margin: 40px auto;
    width: 350px;
    background: #1a1a1a;
    padding: 20px;
    border-radius: 15px;
    text-align: center;
    box-shadow: 0 0 15px #000;
}
img {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 3px solid #444;
    object-fit: cover;
}
h2 {
    margin-bottom: 5px;
}
p {
    color: #bbb;
    font-size: 15px;
}
a.logout {
    display: block;
    margin-top: 20px;
    padding: 10px;
    background: #e63946;
    text-decoration: none;
    color: white;
    border-radius: 10px;
}
a.logout:hover {
    background: #ff4f5d;
}
a.edit {
    display: block;
    margin-top: 15px;
    padding: 10px;
    background: #0a84ff;
    text-decoration: none;
    color: white;
    border-radius: 10px;
}
a.edit:hover {
    background: #1b95ff;
}

/* Navbar */
/* Navbar */
.navbar {
    width: 100%;
    padding: 15px 20px;
    background: #1a1a1a;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo {
    font-size: 22px;
    font-weight: bold;
}

/* Hamburger menu */
.hamburger {
    width: 30px;
    cursor: pointer;
}
.hamburger span {
    display: block;
    width: 100%;
    height: 4px;
    background: white;
    margin: 5px 0;
    border-radius: 5px;
}

/* Side Menu */
.side-menu {
    position: fixed;
    top: 0;
    right: -260px;
    width: 200px;
    height: 100%;
    background: #202020;
    padding-top: 60px;
    display: flex;
    flex-direction: column;
    transition: 0.3s ease;
}

.side-menu a {
    padding: 15px 20px;
    color: white;
    text-decoration: none;
    font-size: 18px;
    border-bottom: 1px solid #333;
}
.side-menu.open {
    right: 0;
}


.side-menu a:hover {
    background: #333;
}

.side-menu .logout {
    color: #ff4d4d;
}


</style>
</head>

<body>
   
    <!-- ========== NAVBAR WITH HAMBURGER MENU ========== -->
    <nav class="navbar">
        <div class="nav-left">
            <span class="logo">Nexo</span>
        </div>

        <div class="nav-right">
            <div class="hamburger" onclick="toggleMenu()">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <!-- ========== SIDE MENU ========== -->
    <div id="sideMenu" class="side-menu">
        <a href="Home.html">أHome</a>
        <a href="profile.html">Profile</a>
        <a href="messages.php">Messages</a>
        <a href="settings.html">Settings</a>
        <a href="logout.php" class="logout">Logout</a>
    </div>



    <div class="container">
            <img src="uploads/<?php echo $profile ?>" alt="Profile">
            <h2><?php echo $fullname ?></h2>
            <p>@<?php echo $username ?></p>
            <p><?php echo $email ?></p>

            <a href="edit_profile.php" class="edit">Edit Profile</a>
            <a href="logout.php" class="logout">Logout</a>
    </div>
    <script>
    function toggleMenu() {
        document.getElementById("sideMenu").classList.toggle("open");
        document.querySelector(".hamburger").classList.toggle("active");
    }
    </script>


</body>
</html>