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

// Stats
$projects_count = $conn->query("SELECT COUNT(*) FROM projects")->fetch_row()[0];
$investors_count = $conn->query("SELECT COUNT(*) FROM users WHERE role='investor'")->fetch_row()[0];

// Projekti
$projects = [];
if ($role == 'entrepreneur') {
    $sql = "SELECT * FROM projects WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $projects[] = $row;
    }
} else {
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
                            <button class="btn btn-success" onclick="openAddModal()">+ Dodaj projekt</button>
                        <?php endif; ?>
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
                                <img src="<?php echo htmlspecialchars($project['image_path']); ?>" alt="Slika" style="max-width:100%; height:auto; margin:1rem 0;">
                            <?php endif; ?>
                            <?php if ($role == 'investor'): ?>
                                <p>Poduzetnik: <?php echo htmlspecialchars($project['entrepreneur_name']); ?></p>
                                <button class="btn btn-primary" onclick="openContactModal(<?php echo $project['entrepreneur_id']; ?>)">Kontaktiraj poduzetnika</button>
                            <?php endif; ?>
                            <?php if ($role == 'entrepreneur'): ?>
                                <div style="margin-top:1rem; display:flex; gap:0.5rem; flex-wrap:wrap;">
                                    <!-- ISPRAVLJENO DUGME ZA UREĐIVANJE PORED BRISANJA -->
                                    <button class="btn btn-outline" onclick="openEditModal(
                                        <?php echo $project['id']; ?>,
                                        '<?php echo addslashes(htmlspecialchars($project['title'])); ?>',
                                        '<?php echo addslashes(htmlspecialchars($project['description'])); ?>',
                                        <?php echo $project['funding_needed']; ?>
                                    )">Uredi</button>
                                    <button class="btn btn-outline" style="background:#e74c3c; color:white;" onclick="deleteProject(<?php echo $project['id']; ?>)">Obriši</button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Modal za dodavanje -->
    <div class="modal" id="addModal" style="display:none">
        <div class="modal-content">
            <span class="close-modal" onclick="document.getElementById('addModal').style.display='none'">&times;</span>
            <h2>Dodaj novi projekt</h2>
            <form action="add_project.php" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Naslov</label>
                    <input type="text" name="title" required>
                </div>
                <div class="form-group">
                    <label>Opis</label>
                    <textarea name="description" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label>Sredstva (€)</label>
                    <input type="number" name="funding_needed" required>
                </div>
                <div class="form-group">
                    <label>Slika (opcionalno)</label>
                    <input type="file" name="image" accept="image/*">
                </div>
                <button type="submit" class="btn btn-primary">Objavi</button>
            </form>
        </div>
    </div>

    <!-- Modal za edit -->
    <div class="modal" id="editModal" style="display:none">
        <div class="modal-content">
            <span class="close-modal" onclick="document.getElementById('editModal').style.display='none'">&times;</span>
            <h2>Uredi projekt</h2>
            <form id="editForm">
                <input type="hidden" id="editId">
                <div class="form-group">
                    <label>Naslov</label>
                    <input type="text" id="editTitle" required>
                </div>
                <div class="form-group">
                    <label>Opis</label>
                    <textarea id="editDescription" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label>Sredstva (€)</label>
                    <input type="number" id="editFunding" required>
                </div>
                <button type="submit" class="btn btn-primary">Spremi promjene</button>
            </form>
        </div>
    </div>

    <!-- Modal za kontakt -->
    <div class="modal" id="contactModal" style="display:none">
        <div class="modal-content">
            <span class="close-modal" onclick="document.getElementById('contactModal').style.display='none'">&times;</span>
            <h2>Pošalji poruku</h2>
            <form action="send_message.php" method="post">
                <input type="hidden" id="contactToId" name="to_id">
                <div class="form-group">
                    <label>Poruka</label>
                    <textarea name="message" rows="4" required></textarea>
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
        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }

        function openEditModal(id, title, description, funding) {
            document.getElementById('editId').value = id;
            document.getElementById('editTitle').value = title;
            document.getElementById('editDescription').value = description;
            document.getElementById('editFunding').value = funding;
            document.getElementById('editModal').style.display = 'block';
        }

        function openContactModal(toId) {
            document.getElementById('contactToId').value = toId;
            document.getElementById('contactModal').style.display = 'block';
        }

        // Edit submit (AJAX)
        document.getElementById('editForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const id = document.getElementById('editId').value;
            const title = document.getElementById('editTitle').value;
            const description = document.getElementById('editDescription').value;
            const funding = document.getElementById('editFunding').value;

            fetch('update_project.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, title, description, funding })
            }).then(() => {
                document.getElementById('editModal').style.display = 'none';
                location.reload(); // Osvježi stranicu nakon update-a
            });
        });

        // Delete (AJAX)
        function deleteProject(id) {
            if (confirm('Sigurno želite obrisati ovaj projekt?')) {
                fetch('delete_project.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                }).then(() => {
                    location.reload(); // Osvježi nakon brisanja
                });
            }
        }

        window.onclick = function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>