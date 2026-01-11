<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$to_id = $_POST['to_id'] ?? null;
$message_text = trim($_POST['message'] ?? '');

if (!$to_id || $message_text === '') {
    die("Nedostaju obavezni podaci.");
}

$stmt = $conn->prepare("
    INSERT INTO messages 
    (from_id, to_id, message, created_at, is_read, deleted_by_receiver) 
    VALUES (?, ?, ?, NOW(), 0, 0)
");
$stmt->bind_param("iis", $user_id, $to_id, $message_text);
$stmt->execute();

// Opcionalno - označi original kao pročitan
if (!empty($_POST['original_message_id'])) {
    $orig = (int)$_POST['original_message_id'];
    $conn->query("UPDATE messages SET is_read = 1 WHERE id = $orig AND to_id = $user_id");
}

header("Location: dashboard.php");
exit;