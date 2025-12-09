<?php
session_start();
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role']; // 'entrepreneur' ili 'investor'

    // Provjera da li email postoji
    $check_sql = "SELECT id FROM users WHERE email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        echo "Email već postoji.";
        exit;
    }

    // Insert u users
    $sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $name, $email, $password, $role);

    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;

        if ($role == 'entrepreneur') {
            $project_name = $_POST['projectName'] ?? null;
            $project_desc = $_POST['projectDescription'] ?? null;
            $funding = $_POST['fundingNeeded'] ?? null;
            $stage = $_POST['businessStage'] ?? null;

            $profile_sql = "INSERT INTO entrepreneur_profiles (user_id, project_name, project_description, funding_needed, business_stage) VALUES (?, ?, ?, ?, ?)";
            $profile_stmt = $conn->prepare($profile_sql);
            $profile_stmt->bind_param("issds", $user_id, $project_name, $project_desc, $funding, $stage);
            $profile_stmt->execute();
        } else {
            $focus = $_POST['investmentFocus'] ?? null;
            $range = $_POST['investmentRange'] ?? null;

            $profile_sql = "INSERT INTO investor_profiles (user_id, investment_focus, investment_range) VALUES (?, ?, ?)";
            $profile_stmt = $conn->prepare($profile_sql);
            $profile_stmt->bind_param("iss", $user_id, $focus, $range);
            $profile_stmt->execute();
        }

        $_SESSION['user_id'] = $user_id;
        $_SESSION['role'] = $role;
        $_SESSION['name'] = $name;

        header("Location: dashboard.php");
        exit;
    } else {
        echo "Greška pri registraciji: " . $conn->error;
    }
}
?>