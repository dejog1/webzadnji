<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
include 'config.php';

$name = $_SESSION['name'] ?? 'Korisnik';
$role = $_SESSION['role'] ?? 'entrepreneur';
?>
<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - InvestIT</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* Veliki i upadljivi button za odjavu */
        .logout-big-btn {
            display: block;
            margin: 2rem auto;
            padding: 1rem 3rem;
            background: #e74c3c;
            color: white;
            font-size: 1.2rem;
            font-weight: 600;
            text-align: center;
            border-radius: 50px;
            text-decoration: none;
            box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4);
            transition: all 0.3s ease;
            max-width: 400px;
        }
        .logout-big-btn:hover {
            background: #c0392b;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(231, 76, 60, 0.5);
        }
    </style>
</head>
<body>

    <header>
        <div class="container">
            <nav class="navbar">
                <div class="navbar-left"></div>
                <div class="navbar-center">
                    <a href="dashboard.php" class="logo">InvestIT</a>
                </div>
                <div class="navbar-right">
                    <div class="user-menu">
                        <span id="userName"><?php echo htmlspecialchars($name); ?></span>
                        <!-- Mali gumb ostaje ako želiš -->
                        <a href="logout.php" class="btn btn-outline">Odjava</a>
                    </div>
                </div>
            </nav>
        </div>
    </header>

    <section class="dashboard" id="dashboard">
        <div class="dashboard-header">
            <div class="container">
                <div class="dashboard-welcome">
                    <div class="user-info">
                        <h2>Dobrodošli, <span id="welcomeUserName"><?php echo htmlspecialchars($name); ?></span>!</h2>
                        <p id="userRoleText">
                            <?php echo $role === 'entrepreneur' ? 'Poduzetnik' : 'Investitor'; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="container">
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-number">12</div>
                    <div class="stat-label">Aktivnih projekata</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">8</div>
                    <div class="stat-label">Registriranih investitora</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">3</div>
                    <div class="stat-label">Novih poruka</div>
                </div>
            </div>

            <!-- VELIKI GUMB ZA ODJAVU – UPADLJIV I JASAN -->
            <div style="text-align: center; margin: 3rem 0;">
                <a href="logout.php" class="logout-big-btn">
                    Odjavi se i vrati na početnu stranicu
                </a>
            </div>

            <!-- Ostale sekcije (investitori/projekti) -->
            <?php if ($role === 'entrepreneur'): ?>
            <div class="dashboard-section">
                <h3 class="section-title">Preporučeni investitori</h3>
                <div class="investors-grid">
                    <p style="text-align:center; color:#888; padding:40px 0;">
                        Uskoro će se prikazivati investitori koji odgovaraju vašem projektu.
                    </p>
                </div>
            </div>
            <?php else: ?>
            <div class="dashboard-section">
                <h3 class="section-title">Preporučeni projekti</h3>
                <div class="projects-grid">
                    <p style="text-align:center; color:#888; padding:40px 0;">
                        Uskoro će se prikazivati najnoviji projekti.
                    </p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2024 InvestIT. Sva prava pridržana.</p>
            </div>
        </div>
    </footer>
</body>
</html>