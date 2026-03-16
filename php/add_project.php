<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'entrepreneur') {
    header("Location: dashboard.php");
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title          = trim($_POST['title'] ?? '');
    $description    = trim($_POST['description'] ?? '');
    $funding_needed = (float)($_POST['funding_needed'] ?? 0);

    if (empty($title)) {
        $errors[] = "Naslov je obavezan.";
    }
    if (empty($description)) {
        $errors[] = "Opis je obavezan.";
    }
    if ($funding_needed <= 0) {
        $errors[] = "Potrebna sredstva moraju biti veća od 0.";
    }

    if (empty($errors)) {
        $user_id = $_SESSION['user_id'];

        $stmt = $conn->prepare("
            INSERT INTO projects (user_id, title, description, funding_needed, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("issd", $user_id, $title, $description, $funding_needed);

        if ($stmt->execute()) {
            $success = "Projekt uspješno dodan!";
        } else {
            $errors[] = "Greška pri dodavanju projekta.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <title>Dodaj projekt - InvestIT</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="container">
    <h2>Dodaj novi projekt</h2>

    <?php if ($success): ?>
        <div class="alert success"><?php echo $success; ?></div>
        <p><a href="dashboard.php">Povratak na dashboard</a></p>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert error">
            <?php foreach ($errors as $err): ?>
                <p><?php echo $err; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <label>Naslov projekta:</label>
        <input type="text" name="title" required>

        <label>Opis projekta:</label>
        <textarea name="description" rows="6" required></textarea>

        <label>Potrebna sredstva (€):</label>
        <input type="number" name="funding_needed" step="0.01" min="1" required>

        <!-- Polje za sliku je uklonjeno -->

        <button type="submit" class="btn btn-primary">Dodaj projekt</button>
    </form>

    <p><a href="dashboard.php">Odustani i vrati se na dashboard</a></p>
</div>

</body>
</html>