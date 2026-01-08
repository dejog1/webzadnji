<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'entrepreneur') {
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $funding_needed = $_POST['funding_needed'] ?? 0;

    // Upload slike
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "uploads/";
        $image_name = basename($_FILES["image"]["name"]);
        $target_file = $target_dir . time() . '_' . $image_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Validacija: samo jpg/png/gif, max 2MB
        if (in_array($imageFileType, ['jpg', 'png', 'jpeg', 'gif']) && $_FILES["image"]["size"] <= 2000000) {
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $image_path = $target_file;
            }
        }
    }

    // Spremi u bazu
    $sql = "INSERT INTO projects (user_id, title, description, funding_needed, image_path) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issds", $user_id, $title, $description, $funding_needed, $image_path);
    if ($stmt->execute()) {
        header("Location: dashboard.php?success=project_added");
    } else {
        header("Location: dashboard.php?error=project_add_failed");
    }
    exit;
}
?>