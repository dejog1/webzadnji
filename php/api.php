<?php
include 'config.php';

$action = $_GET['action'] ?? '';

if ($action == 'get_projects') {
    $role = $_SESSION['role'];
    $user_id = $_SESSION['user_id'];

    if ($role == 'entrepreneur') {
        $sql = "SELECT * FROM projects WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $sql = "SELECT p.*, u.name as entrepreneur_name, u.id as entrepreneur_id FROM projects p JOIN users u ON p.user_id = u.id";
        $result = $conn->query($sql);
    }

    $projects = [];
    while ($row = $result->fetch_assoc()) {
        $projects[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($projects);
    exit;
}
if ($action == 'update_project') {
    $id = $_POST['id'];
    $title = $_POST['title'];
    $sql = "UPDATE projects SET title = ? WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $title, $id, $user_id);
    $stmt->execute();
    echo json_encode(['success' => true]);
    exit;
}

if ($action == 'delete_project') {
    $id = $_POST['id'];
    $sql = "DELETE FROM projects WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    echo json_encode(['success' => true]);
    exit;
}
?>

