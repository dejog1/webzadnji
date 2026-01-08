<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'investor') {
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $from_id = $_SESSION['user_id'];
    $to_id = $_POST['to_id'] ?? 0;
    $message = $_POST['message'] ?? '';

    if ($to_id > 0 && !empty($message)) {
        $sql = "INSERT INTO messages (from_id, to_id, message) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $from_id, $to_id, $message);
        if ($stmt->execute()) {
            header("Location: dashboard.php?success=message_sent");
        } else {
            header("Location: dashboard.php?error=message_failed");
        }
    } else {
        header("Location: dashboard.php?error=invalid_data");
    }
    exit;
}
?>