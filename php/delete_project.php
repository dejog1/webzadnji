<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'entrepreneur') {
    http_response_code(403);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'];

$sql = "DELETE FROM projects WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id, $_SESSION['user_id']);
$stmt->execute();

echo json_encode(['success' => true]);
?>