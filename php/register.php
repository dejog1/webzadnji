<?php
session_start();
require_once 'config.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name   = trim($_POST['full_name'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $password    = $_POST['password'] ?? '';
    $role        = $_POST['role'] ?? '';

    // Validacija
    if (empty($full_name)) $errors[] = "Ime i prezime su obavezni.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Ispravna email adresa je obavezna.";
    if (strlen($password) < 6) $errors[] = "Lozinka mora imati najmanje 6 znakova.";
    if (!in_array($role, ['entrepreneur', 'investor'])) $errors[] = "Odaberite ulogu.";

    // Provjera postoji li email
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "Email adresa je već registrirana.";
        }
    }

    // Registracija
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("
            INSERT INTO users (name, email, password, role, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("ssss", $full_name, $email, $hashed_password, $role);

        if ($stmt->execute()) {
            $user_id = $conn->insert_id;

            // === AUTO LOGIN ===
            $_SESSION['user_id'] = $user_id;
            $_SESSION['role']    = $role;
            $_SESSION['name']    = $full_name;

            // Preusmjeri na dashboard
            header("Location: dashboard.php");
            exit;
        } else {
            $errors[] = "Greška pri registraciji. Pokušajte ponovo.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <title>Registracija - InvestIT</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="container">
    <h2>Registracija</h2>

    <?php if (!empty($errors)): ?>
        <div class="alert error">
            <?php foreach ($errors as $err): ?>
                <p><?= htmlspecialchars($err) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <label>Ime i prezime:</label>
        <input type="text" name="full_name" value="<?= htmlspecialchars($full_name ?? '') ?>" required>

        <label>Email:</label>
        <input type="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required>

        <label>Lozinka:</label>
        <input type="password" name="password" required minlength="6">

        <label>Uloga:</label>
        <select name="role" required>
            <option value="">-- Odaberite ulogu --</option>
            <option value="entrepreneur" <?= ($role ?? '') === 'entrepreneur' ? 'selected' : '' ?>>Poduzetnik</option>
            <option value="investor" <?= ($role ?? '') === 'investor' ? 'selected' : '' ?>>Investitor</option>
        </select>

        <button type="submit" class="btn btn-primary">Registriraj se</button>
    </form>

    <p>Već imate račun? <a href="login.php">Prijavite se</a></p>
</div>

</body>
</html>