<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'entrepreneur') {
    echo json_encode(['success' => false, 'error' => 'Niste autorizirani']);
    exit;
}

$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;

if ($project_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Neispravan ID projekta']);
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("DELETE FROM projects WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $project_id, $user_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Projekt uspješno obrisan']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Projekt nije pronađen ili nemate pravo']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Greška pri brisanju: ' . $conn->error]);
}

$stmt->close();
$conn->close();
exit;