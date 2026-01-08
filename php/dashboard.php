<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}
include 'config.php';

$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'];
$role = $_SESSION['role'];

// Fetch stats
$projects_count = $conn->query("SELECT COUNT(*) FROM projects")->fetch_row()[0];
$investors_count = $conn->query("SELECT COUNT(*) FROM users WHERE role='investor'")->fetch_row()[0];

// Fetch projekata
$projects = [];
if ($role == 'entrepreneur') {
    // Za poduzetnike: njihovi projekti
    $sql = "SELECT * FROM projects WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $projects[] = $row;
    }
} else {
    // Za investitore: svi projekti sa poduzetnikom
    $sql = "SELECT p.*, u.name as entrepreneur_name, u.id as entrepreneur_id 
            FROM projects p JOIN users u ON p.user_id = u.id";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $projects[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - InvestIT</title>
    <link rel="stylesheet" href="../css/style.css">
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
                        <span><?php echo htmlspecialchars($name); ?></span>
                        <a href="logout.php" class="btn btn-outline">Odjava</a>
                    </div>
                </div>
            </nav>
        </div>
    </header>

    <section class="dashboard">
        <div class="dashboard-header">
            <div class="container">
                <div class="dashboard-welcome">
                    <div class="user-info">
                        <h2>Dobrodošli, <?php echo htmlspecialchars($name); ?>!</h2>
                        <p><?php echo $role == 'entrepreneur' ? 'Poduzetnik' : 'Investitor'; ?></p>
                    </div>
                    <div class="dashboard-actions">
                        <?php if ($role == 'entrepreneur'): ?>
                            <button class="btn btn-success" onclick="openProjectModal()">+ Dodaj projekt</button>
                        <?php endif; ?>
                        <!-- UMJESTO "Moj Profil" – SADA JE "Odjava" -->
                        <a href="logout.php" class="btn btn-primary">Odjava</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="container">
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $projects_count; ?></div>
                    <div class="stat-label">Aktivnih projekata</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $investors_count; ?></div>
                    <div class="stat-label">Registriranih investitora</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">0</div>
                    <div class="stat-label">Novih poruka</div>
                </div>
            </div>

            <h3 class="section-title"><?php echo $role == 'entrepreneur' ? 'Moji projekti' : 'Dostupni projekti'; ?></h3>
            <div class="projects-grid">
                <?php if (empty($projects)): ?>
                    <p style="text-align:center; color:#777;">Nema projekata za prikaz.</p>
                <?php else: ?>
                    <?php foreach ($projects as $project): ?>
                        <div class="feature-card">
                            <h3><?php echo htmlspecialchars($project['title']); ?></h3>
                            <p><?php echo htmlspecialchars($project['description']); ?></p>
                            <p>Potrebna sredstva: €<?php echo number_format($project['funding_needed'], 2); ?></p>
                            <?php if ($project['image_path']): ?>
                                <img src="<?php echo htmlspecialchars($project['image_path']); ?>" alt="Projekt slika" style="max-width:100%; height:auto; margin:1rem 0;">
                            <?php endif; ?>
                            <?php if ($role == 'investor'): ?>
                                <p>Poduzetnik: <?php echo htmlspecialchars($project['entrepreneur_name']); ?></p>
                                <button class="btn btn-primary" onclick="openContactModal(<?php echo $project['entrepreneur_id']; ?>)">Kontaktiraj poduzetnika</button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Modal za dodavanje projekta (samo za poduzetnike) -->
    <div class="modal" id="projectModal" style="display:none">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('projectModal')">&times;</span>
            <h2>Dodaj novi projekt</h2>
            <form action="add_project.php" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Naslov projekta</label>
                    <input type="text" name="title" required>
                </div>
                <div class="form-group">
                    <label>Opis ideje</label>
                    <textarea name="description" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label>Potrebna sredstva (€)</label>
                    <input type="number" name="funding_needed" required>
                </div>
                <div class="form-group">
                    <label>Slika projekta (opcionalno)</label>
                    <input type="file" name="image" accept="image/*">
                </div>
                <button type="submit" class="btn btn-primary">Objavi projekt</button>
            </form>
        </div>
    </div>

    <!-- Modal za kontakt (samo za investitore) -->
    <div class="modal" id="contactModal" style="display:none">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('contactModal')">&times;</span>
            <h2>Pošalji poruku poduzetniku</h2>
            <form action="send_message.php" method="post">
                <input type="hidden" id="contactToId" name="to_id">
                <div class="form-group">
                    <label>Vaša poruka</label>
                    <textarea name="message" rows="4" required placeholder="Napišite poruku..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Pošalji</button>
            </form>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2024 InvestIT. Sva prava pridržana.</p>
            </div>
        </div>
    </footer>

    <script>
        function openProjectModal() {
            document.getElementById('projectModal').style.display = 'block';
        }

        function openContactModal(toId) {
            document.getElementById('contactToId').value = toId;
            document.getElementById('contactModal').style.display = 'block';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        window.onclick = function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>