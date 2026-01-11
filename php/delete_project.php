<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'entrepreneur') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Zamijeni dio s $input i $data ovim:
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

// Ostatak koda (SQL prepare, bind_param, execute) ostaje isti

$sql = "DELETE FROM projects WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id, $_SESSION['user_id']);

if ($stmt->execute()) {
    // Umjesto echo json_encode, vrati korisnika na dashboard
    header("Location: dashboard.php?status=deleted");
    exit;
} else {
    header("Location: dashboard.php?status=error");
    exit;
}

$stmt->close();
exit;
?>