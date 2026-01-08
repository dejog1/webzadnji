<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'config.php'; // config je u istom folderu

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'entrepreneur';

$projects = [];

if ($role === 'entrepreneur') {
    // Poduzetnik vidi samo svoje projekte
    $sql = "SELECT id, title, description, funding_needed, image_path FROM projects WHERE user_id = ? ORDER BY id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Investitor vidi sve projekte + ime poduzetnika
    $sql = "SELECT p.id, p.title, p.description, p.funding_needed, p.image_path, u.name AS entrepreneur_name, u.id AS entrepreneur_id 
            FROM projects p 
            JOIN users u ON p.user_id = u.id 
            ORDER BY p.id DESC";
    $result = $conn->query($sql);
}

while ($row = $result->fetch_assoc()) {
    $projects[] = $row;
}

echo json_encode($projects);
?>