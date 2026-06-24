<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}
include 'config.php';

$user_id = $_SESSION['user_id'];
$name    = $_SESSION['name'];
$role    = $_SESSION['role'];

// === STATISTIKA ===
$projects_count  = $conn->query("SELECT COUNT(*) FROM projects")->fetch_row()[0] ?? 0;
$investors_count = $conn->query("SELECT COUNT(*) FROM users WHERE role='investor'")->fetch_row()[0] ?? 0;

// Broj novih poruka (sa zaštitom)
$new_messages = 0;
$stmt = $conn->prepare("SELECT COUNT(*) FROM messages WHERE to_id = ? AND is_read = 0 AND deleted_by_receiver = 0");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $new_messages = $stmt->get_result()->fetch_row()[0] ?? 0;
    $stmt->close();
}

// Projekti
$projects = [];
if ($role === 'entrepreneur') {
    $stmt = $conn->prepare("SELECT * FROM projects WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $projects[] = $row;
} else {
    $res = $conn->query("SELECT p.*, u.name AS entrepreneur_name, u.id AS entrepreneur_id FROM projects p JOIN users u ON p.user_id = u.id");
    while ($row = $res->fetch_assoc()) $projects[] = $row;
}

// Poruke
$messages = [];
$stmt = $conn->prepare("SELECT m.*, u.name AS from_name FROM messages m JOIN users u ON m.from_id = u.id WHERE m.to_id = ? AND m.deleted_by_receiver = 0 ORDER BY m.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $messages[] = $row;
?>

<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Dashboard - InvestIT</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <style>
        .message-card { position:relative; padding:16px; margin-bottom:16px; border:1px solid #ddd; border-radius:8px; background:white; }
        .message-card.unread { background:#f0f8ff; border-color:#a0d0ff; }
        .delete-msg-btn { position:absolute; top:12px; right:12px; background:#e74c3c; color:white; border:none; border-radius:50%; width:28px; height:28px; font-size:18px; line-height:28px; cursor:pointer; }
        .delete-msg-btn:hover { background:#c0392b; }
        #notification { 
            position: fixed; top: 20px; left: 50%; transform: translateX(-50%);
            padding: 12px 24px; border-radius: 6px; color: white; font-weight: bold; z-index: 3000;
            display: none; min-width: 300px; text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        #notification.success { background: #28a745; }
        #notification.error   { background: #dc3545; }
        .feature-card { 
            transition: all 0.3s ease; 
        }
        .feature-card.removing {
            opacity: 0;
            transform: translateY(20px);
            height: 0;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
    </style>
</head>
<body>

<header>
    <div class="container navbar">
        <a href="dashboard.php" class="logo">InvestIT</a>
        <div class="user-menu">
            <span id="userName"><?php echo htmlspecialchars($name); ?></span>
            <a href="logout.php" class="btn btn-outline">Odjava</a>
        </div>
    </div>
</header>

<section class="dashboard">
    <div class="container">
        <h2>Dobrodošli, <?php echo htmlspecialchars($name); ?>!</h2>
        <p><?php echo $role === 'entrepreneur' ? 'Poduzetnik' : 'Investitor'; ?></p>

        <?php if ($role === 'entrepreneur'): ?>
            <button class="btn btn-success" onclick="openAddModal()">+ Dodaj projekt</button>
        <?php endif; ?>

        <div class="stats-cards">
            <div class="stat-card"><div class="stat-number" id="projectsCount"><?php echo $projects_count; ?></div><div class="stat-label">Projekata</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo $investors_count; ?></div><div class="stat-label">Investitora</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo $new_messages; ?></div><div class="stat-label">Novih poruka</div></div>
        </div>

        <h3 class="section-title"><?php echo $role === 'entrepreneur' ? 'Poruke investitora' : 'Poruke od poduzetnika'; ?></h3>

        <?php if (empty($messages)): ?>
            <p style="color:#777;">Nemate poruka.</p>
        <?php else: ?>
            <?php foreach ($messages as $msg): ?>
                <div class="message-card <?php echo $msg['is_read'] ? '' : 'unread'; ?>" data-message-id="<?php echo $msg['id']; ?>">
                    <button class="delete-msg-btn" onclick="deleteMessage(<?php echo $msg['id']; ?>)">×</button>
                    <p><strong>Od:</strong> <?php echo htmlspecialchars($msg['from_name']); ?></p>
                    <p><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                    <small><?php echo $msg['created_at']; ?></small>
                    <br><br>
                    <button class="btn btn-primary" onclick="openReplyModal(<?php echo $msg['from_id']; ?>, <?php echo $msg['id']; ?>)">Odgovori</button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <h3 class="section-title"><?php echo $role === 'entrepreneur' ? 'Moji projekti' : 'Dostupni projekti'; ?></h3>

        <div class="projects-grid" id="projectsGrid">
            <?php foreach ($projects as $project): ?>
                <div class="feature-card" data-project-id="<?php echo $project['id']; ?>">
                    <h3><?php echo htmlspecialchars($project['title']); ?></h3>
                    <p><?php echo htmlspecialchars($project['description']); ?></p>
                    <p>€<?php echo number_format($project['funding_needed'], 2); ?></p>

                    <?php if ($role === 'investor'): ?>
                        <p>Poduzetnik: <?php echo htmlspecialchars($project['entrepreneur_name']); ?></p>
                        <button class="btn btn-primary" onclick="openContactModal(<?php echo $project['entrepreneur_id']; ?>)">Kontaktiraj</button>
                    <?php endif; ?>

                    <?php if ($role === 'entrepreneur'): ?>
                        <button class="btn btn-edit" onclick='openEditModal(<?php echo (int)$project["id"]; ?>, <?php echo json_encode($project["title"], JSON_HEX_QUOT | JSON_HEX_TAG); ?>, <?php echo json_encode($project["description"], JSON_HEX_QUOT | JSON_HEX_TAG); ?>, <?php echo (float)$project["funding_needed"]; ?>)'>Uredi</button>
                        <button class="btn btn-delete" onclick="deleteProject(<?php echo (int)$project['id']; ?>)">Obriši</button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- MODALI -->

<div class="modal" id="addModal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal('addModal')">×</span>
        <h3>Dodaj novi projekt</h3>
        <form id="addProjectForm">
            <input type="text" name="title" placeholder="Naslov" required>
            <textarea name="description" placeholder="Opis" required></textarea>
            <input type="number" name="funding_needed" placeholder="Sredstva (€)" step="0.01" min="1" required>
            <button type="submit" class="btn btn-primary" style="width:100%; margin-top:15px;">Spremi</button>
        </form>
    </div>
</div>

<div class="modal" id="contactModal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal('contactModal')">×</span>
        <form action="send_message.php" method="post">
            <input type="hidden" name="to_id" id="contactToId">
            <textarea name="message" placeholder="Vaša poruka..." required></textarea>
            <button class="btn btn-primary">Pošalji</button>
        </form>
    </div>
</div>

<div class="modal" id="replyModal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal('replyModal')">×</span>
        <form action="send_message.php" method="post">
            <input type="hidden" name="to_id" id="replyToId">
            <input type="hidden" name="original_message_id" id="originalMessageId">
            <textarea name="message" placeholder="Vaš odgovor..." required></textarea>
            <button class="btn btn-success">Odgovori</button>
        </form>
    </div>
</div>

<div class="modal" id="editModal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal('editModal')">×</span>
        <h3>Uredi projekt</h3>
        <form action="update_project.php" method="post">
            <input type="hidden" name="project_id" id="edit_project_id">
            <label>Naslov:</label>
            <input type="text" name="title" id="edit_title" required>
            <label>Opis:</label>
            <textarea name="description" id="edit_description" rows="5" required></textarea>
            <label>Potrebna sredstva (€):</label>
            <input type="number" name="funding_needed" id="edit_funding_needed" step="0.01" required>
            <div style="margin-top:20px;">
                <button type="submit" class="btn btn-primary">Spremi promjene</button>
                <button type="button" class="btn btn-outline" onclick="closeModal('editModal')">Odustani</button>
            </div>
        </form>
    </div>
</div>

<!-- NOTIFIKACIJA -->
<div id="notification"></div>

<script>
function showNotification(message, type = 'success') {
    const notif = document.getElementById('notification');
    notif.textContent = message;
    notif.className = type;
    notif.style.display = 'block';
    setTimeout(() => notif.style.display = 'none', 4000);
}

function openAddModal() {
    document.getElementById('addModal').style.display = 'block';
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

function openContactModal(id) {
    document.getElementById('contactToId').value = id;
    document.getElementById('contactModal').style.display = 'block';
}

function openReplyModal(fromId, messageId) {
    document.getElementById('replyToId').value = fromId;
    document.getElementById('originalMessageId').value = messageId;
    document.getElementById('replyModal').style.display = 'block';
}

function openEditModal(id, title, description, funding) {
    document.getElementById('edit_project_id').value = id;
    document.getElementById('edit_title').value = title;
    document.getElementById('edit_description').value = description;
    document.getElementById('edit_funding_needed').value = funding;
    document.getElementById('editModal').style.display = 'block';
}

// BRISANJE PROJEKTA PREKO AJAX-a (bez reload-a)
function deleteProject(projectId) {
    if (!confirm('Sigurno želite obrisati projekt?\nOva radnja se NE može poništiti!')) return;

    fetch('delete_project_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'project_id=' + projectId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const card = document.querySelector(`.feature-card[data-project-id="${projectId}"]`);
            if (card) {
                card.classList.add('removing');
                setTimeout(() => {
                    card.remove();
                    showNotification('Projekt uspješno obrisan!', 'success');

                    // Ažuriraj brojač projekata
                    const countEl = document.getElementById('projectsCount');
                    if (countEl) {
                        let current = parseInt(countEl.textContent) || 0;
                        if (current > 0) countEl.textContent = current - 1;
                    }
                }, 300); // vrijeme animacije
            }
        } else {
            showNotification('Greška: ' + (data.error || 'Nepoznata greška'), 'error');
        }
    })
    .catch(err => {
        console.error(err);
        showNotification('Došlo je do greške pri brisanju', 'error');
    });
}

// DODAVANJE PROJEKTA PREKO AJAX-a (bez reload-a)
document.getElementById('addProjectForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('add_project_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message || 'Projekt uspješno dodan!', 'success');
            closeModal('addModal');

            // Dinamički dodaj novu karticu na početak liste
            const grid = document.getElementById('projectsGrid');
            const newCard = document.createElement('div');
            newCard.className = 'feature-card';
            newCard.setAttribute('data-project-id', data.project_id);

            newCard.innerHTML = `
                <h3>${escapeHtml(data.title || 'Novi projekt')}</h3>
                <p>${escapeHtml(data.description || 'Opis projekta')}</p>
                <p>€${Number(data.funding_needed || 0).toLocaleString('hr-HR', {minimumFractionDigits: 2})}</p>
                <button class="btn btn-edit" onclick='openEditModal(${data.project_id}, "${escapeHtml(data.title || '').replace(/"/g,'\\"')}", "${escapeHtml(data.description || '').replace(/"/g,'\\"')}", ${data.funding_needed || 0})'>Uredi</button>
                <button class="btn btn-delete" onclick="deleteProject(${data.project_id})">Obriši</button>
            `;

            // Dodaj na početak
            grid.insertBefore(newCard, grid.firstChild);

            // Resetiraj formu
            this.reset();

            // Ažuriraj brojač projekata
            const countEl = document.getElementById('projectsCount');
            if (countEl) {
                let current = parseInt(countEl.textContent) || 0;
                countEl.textContent = current + 1;
            }
        } else {
            showNotification('Greška: ' + (data.error || 'Nepoznata greška'), 'error');
        }
    })
    .catch(err => {
        console.error(err);
        showNotification('Došlo je do greške pri spremanju', 'error');
    });
});

// Pomoćna funkcija za sigurno ispisivanje HTML-a
function escapeHtml(unsafe) {
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Brisanje poruke (ostaje isto)
function deleteMessage(messageId) {
    if (!confirm('Želite li obrisati poruku?')) return;
    fetch('delete_message.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'message_id=' + messageId
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.querySelector(`[data-message-id="${messageId}"]`)?.remove();
            showNotification('Poruka obrisana', 'success');
        } else {
            showNotification('Greška: ' + (data.error || 'Nepoznato'), 'error');
        }
    })
    .catch(() => showNotification('Greška pri komunikaciji', 'error'));
}
</script>

</body>
</html>