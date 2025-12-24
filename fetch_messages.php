<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['ok'=>false,'error'=>'not_logged_in']));
}

$me_id = (int)$_SESSION['user_id'];
$other_username = $_GET['username'] ?? '';
$after = isset($_GET['after_id']) ? (int)$_GET['after_id'] : 0;

if (!$other_username) {
    exit(json_encode(['ok'=>false,'error'=>'invalid_user']));
}

// جلب id الخاص بالمستخدم الهدف من username
$stmt = $conn->prepare("SELECT id FROM users WHERE username=? LIMIT 1");
$stmt->bind_param("s", $other_username);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
if (!$user) {
    exit(json_encode(['ok'=>false,'error'=>'target_not_found']));
}
$other_id = $user['id'];

// جلب الرسائل
if ($after > 0) {
    $stmt = $conn->prepare("
        SELECT m.id, u1.username AS sender_username, u2.username AS receiver_username, m.message, m.created_at
        FROM messages m
        JOIN users u1 ON m.sender_id = u1.id
        JOIN users u2 ON m.receiver_id = u2.id
        WHERE ((m.sender_id=? AND m.receiver_id=?) OR (m.sender_id=? AND m.receiver_id=?)) AND m.id>?
        ORDER BY m.id ASC
    ");
    $stmt->bind_param("iiiii", $me_id, $other_id, $other_id, $me_id, $after);
} else {
    $stmt = $conn->prepare("
        SELECT m.id, u1.username AS sender_username, u2.username AS receiver_username, m.message, m.created_at
        FROM messages m
        JOIN users u1 ON m.sender_id = u1.id
        JOIN users u2 ON m.receiver_id = u2.id
        WHERE (m.sender_id=? AND m.receiver_id=?) OR (m.sender_id=? AND m.receiver_id=?)
        ORDER BY m.id ASC
        LIMIT 200
    ");
    $stmt->bind_param("iiii", $me_id, $other_id, $other_id, $me_id);
}

$stmt->execute();
$res = $stmt->get_result();
$messages = [];
while ($row = $res->fetch_assoc()) {
    $messages[] = $row;
}

exit(json_encode(['ok'=>true,'messages'=>$messages]));
