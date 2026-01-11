<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Niste prijavljeni']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$message_id = isset($_POST['message_id']) ? (int)$_POST['message_id'] : 0;

if ($message_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Neispravan ID poruke']);
    exit;
}

$stmt = $conn->prepare("
    UPDATE messages 
    SET deleted_by_receiver = 1 
    WHERE id = ? AND to_id = ?
");
$stmt->bind_param("ii", $message_id, $user_id);

$success = $stmt->execute();

echo json_encode(['success' => $success]);

$stmt->close();
$conn->close();