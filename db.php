<?php
$host = "localhost";
$user = "Nasro"; 
$pass = "Nasro2010";
$db = "Nexo";

$conn = mysqli_connect($host, $user, $pass, $db);

// فحص الاتصال
if(!$conn){
    die("Connection failed: " . mysqli_connect_error());
}
?>
