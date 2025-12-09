<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'entrepreneur') {
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $title = $_POST['projectTitle'];
    $description = $_POST['projectDescription'];
    $funding = $_POST['projectFunding'];
    $category = $_POST['projectCategory'] ?? null;

    $sql = "INSERT INTO projects (user_id, title, description, funding_needed, category) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issds", $user_id, $title, $description, $funding, $category);

    if ($stmt->execute()) {
        header("Location: dashboard.php");
        exit;
    } else {
        echo "Greška pri dodavanju projekta: " . $conn->error;
    }
}
?>