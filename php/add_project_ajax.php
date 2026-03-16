<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'entrepreneur') {
    echo json_encode(['success' => false, 'error' => 'Niste autorizirani']);
    exit;
}

$title          = trim($_POST['title'] ?? '');
$description    = trim($_POST['description'] ?? '');
$funding_needed = (float)($_POST['funding_needed'] ?? 0);

if (empty($title) || empty($description) || $funding_needed <= 0) {
    echo json_encode(['success' => false, 'error' => 'Sva polja su obavezna i ispravna']);
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    INSERT INTO projects (user_id, title, description, funding_needed, created_at)
    VALUES (?, ?, ?, ?, NOW())
");
$stmt->bind_param("issd", $user_id, $title, $description, $funding_needed);

if ($stmt->execute()) {
    $project_id = $conn->insert_id; // novi ID projekta

    echo json_encode([
        'success'       => true,
        'message'       => 'Projekt uspješno dodan!',
        'project_id'    => $project_id,
        'title'         => $title,
        'description'   => $description,
        'funding_needed'=> $funding_needed
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Greška pri spremanju: ' . $conn->error]);
}

$stmt->close();
$conn->close();
exit;