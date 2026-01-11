<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Niste prijavljeni';
    echo json_encode($response);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;

if ($project_id <= 0) {
    $response['message'] = 'Neispravan ID projekta';
    echo json_encode($response);
    exit;
}

// Provjera vlasništva
$stmt = $conn->prepare("SELECT id FROM projects WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $project_id, $user_id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    $response['message'] = 'Nemate pravo uređivati ovaj projekt';
    echo json_encode($response);
    exit;
}

// Osnovna validacija
$title          = trim($_POST['title'] ?? '');
$description    = trim($_POST['description'] ?? '');
$funding_needed = isset($_POST['funding_needed']) ? (float)$_POST['funding_needed'] : 0;

if (empty($title) || empty($description) || $funding_needed <= 0) {
    $response['message'] = 'Nedostaju obavezni podaci';
    echo json_encode($response);
    exit;
}

// Update
$stmt = $conn->prepare("
    UPDATE projects 
    SET title = ?, 
        description = ?, 
        funding_needed = ?
    WHERE id = ? AND user_id = ?
");
$stmt->bind_param("ssdii", $title, $description, $funding_needed, $project_id, $user_id);

if ($stmt->execute()) {
    // Ako je uspješno, vrati se na dashboard s porukom uspjeha
    header("Location: dashboard.php?msg=success");
    exit;
} else {
    // Ako je greška, ispiši je ili vrati s error porukom
    die("Greška pri ažuriranju baze: " . $conn->error);
}
exit;