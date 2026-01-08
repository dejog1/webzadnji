<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'entrepreneur') {
    header("Location: ../index.php");
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['id']) || !is_numeric($data['id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$id = (int)$data['id'];
$title = $data['title'] ?? '';
$description = $data['description'] ?? '';
$funding = $data['funding'] ?? 0;

$sql = "UPDATE projects SET title = ?, description = ?, funding_needed = ? WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssdii", $title, $description, $funding, $id, $_SESSION['user_id']);
$stmt->execute();

echo json_encode(['success' => true]);
?>