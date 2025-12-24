<?php
session_start();
require_once 'db.php';

$me_id = $_SESSION['user_id'];              // id المرسل من الجلسة
$receiver_username = $_POST['receiver_username'] ?? '';
$message = $_POST['message'] ?? '';

if (!$receiver_username || !$message) {
    echo json_encode(['ok'=>false,'error'=>'Missing data']);
    exit;
}

// تحويل username المستقبل إلى id
$stmt = $conn->prepare("SELECT id FROM users WHERE username=? LIMIT 1");
$stmt->bind_param("s", $receiver_username);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();

if (!$user) {
    echo json_encode(['ok'=>false,'error'=>'Receiver not found']);
    exit;
}

$receiver_id = $user['id'];

// إدراج الرسالة باستخدام sender_id و receiver_id
$stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message, created_at) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("iis", $me_id, $receiver_id, $message);

if (!$stmt->execute()) {
    echo json_encode(['ok'=>false,'error'=>$stmt->error]);
    exit;
}

echo json_encode(['ok'=>true]);
