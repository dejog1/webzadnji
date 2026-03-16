<?php
// Siguran session_start()
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ako je korisnik već prijavljen → preusmjeri na dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />

    <title>InvestIT - Platforma za poduzetnike i investitore</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>" />

    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        html, body { height:100%; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; line-height:1.6; color:#333; background:#f8f9fa; }
        .container { width:90%; max-width:1200px; margin:0 auto; padding:0 15px; }

        header { background:#fff; box-shadow:0 2px 10px rgba(0,0,0,0.1); position:sticky; top:0; z-index:1000; }
        .navbar { display:flex; justify-content:space-between; align-items:center; padding:1rem 0; }
        .logo { font-size:1.8rem; font-weight:bold; color:#007bff; text-decoration:none; }

        .btn { padding:10px 20px; border-radius:6px; cursor:pointer; font-weight:600; text-decoration:none; display:inline-block; transition:all 0.3s; }
        .btn-primary { background:#0062cc; color:white; }
        .btn-primary:hover { background:#0052b0; }
        .btn-outline { background:transparent; border:2px solid #007bff; color:#007bff; }
        .btn-outline:hover { background:#007bff; color:white; }

        .hero { background:linear-gradient(135deg,#007bff,#0056b3); color:white; padding:100px 0; text-align:center; }
        .hero h1 { font-size:3rem; margin-bottom:1rem; }
        .hero p { font-size:1.3rem; max-width:700px; margin:0 auto 2rem; opacity:0.95; }
        .hero-buttons { display:flex; gap:20px; justify-content:center; flex-wrap:wrap; }
        .hero .btn { font-size:1.2rem; padding:15px 30px; min-width:250px; }

        .modal {
            display:none;
            position:fixed;
            z-index:2000;
            left:0; top:0;
            width:100%; height:100%;
            background:rgba(0,0,0,0.75);
            overflow-y:auto;
        }
        .modal-content {
            background:white;
            margin:5% auto;
            padding:2.5rem;
            width:90%;
            max-width:520px;
            border-radius:12px;
            position:relative;
            box-shadow:0 10px 30px rgba(0,0,0,0.3);
        }
        .close-modal { position:absolute; top:15px; right:20px; font-size:32px; cursor:pointer; color:#aaa; }
        .close-modal:hover { color:#000; }

        .user-type-selector { display:flex; margin:1.5rem 0; border-radius:8px; overflow:hidden; }
        .user-type { padding:14px; flex:1; text-align:center; background:#f0f0f0; cursor:pointer; transition:all 0.3s; }
        .user-type.active { background:#007bff; color:white; }

        .form-group { margin-bottom:1.2rem; }
        label { display:block; margin-bottom:0.5rem; font-weight:600; }
        input, select { width:100%; padding:12px; border:1px solid #ddd; border-radius:6px; font-size:1rem; }
        input:focus, select:focus { outline:none; border-color:#007bff; box-shadow:0 0 0 3px rgba(0,123,255,0.2); }
    </style>
</head>
<body>

    <!-- HEADER -->
    <header>
        <div class="container">
            <nav class="navbar">
                <a href="index.php" class="logo">InvestIT</a>
                <div>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <button class="btn btn-outline" onclick="openModal('loginModal')">Prijava</button>
                        <button class="btn btn-primary" onclick="openModal('registerModal')">Registracija</button>
                    <?php else: ?>
                        <span style="margin-right:15px; font-weight:600;">Zdravo, <?php echo htmlspecialchars($_SESSION['name'] ?? ''); ?>!</span>
                        <a href="dashboard.php" class="btn btn-primary">Dashboard</a>
                        <a href="logout.php" class="btn btn-outline">Odjava</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <!-- HERO -->
    <section class="hero">
        <div class="container">
            <h1>Povezujemo poduzetnike i investitore</h1>
            <p>InvestIT je platforma koja omogućuje mladim poduzetnicima da pronađu investitore za svoje ideje, a investitorima da otkriju perspektivne projekte.</p>
            <div class="hero-buttons">
                <button class="btn btn-primary" onclick="openModal('registerModal'); setRole('entrepreneur')">
                    Registriraj se kao poduzetnik
                </button>
                <button class="btn btn-outline" onclick="openModal('registerModal'); setRole('investor')">
                    Registriraj se kao investitor
                </button>
            </div>
        </div>
    </section>

    <!-- LOGIN MODAL -->
    <div class="modal" id="loginModal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('loginModal')">×</span>
            <h2>Prijava</h2>
            <form action="login.php" method="post">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Lozinka</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%; padding:14px;">Prijavi se</button>
            </form>
        </div>
    </div>

    <!-- REGISTER MODAL – samo osnovna polja -->
    <div class="modal" id="registerModal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('registerModal')">×</span>
            <h2>Registracija</h2>

            <div class="user-type-selector">
                <div class="user-type active" data-type="entrepreneur" onclick="setRole('entrepreneur')">Poduzetnik</div>
                <div class="user-type" data-type="investor" onclick="setRole('investor')">Investitor</div>
            </div>

            <form action="register.php" method="post">
                <input type="hidden" name="role" id="roleInput" value="entrepreneur">

                <div class="form-group">
                    <label>Ime i prezime *</label>
                    <input type="text" name="full_name" required placeholder="npr. Ivan Horvat">
                </div>

                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" required>
                </div>

                <div class="form-group">
                    <label>Lozinka *</label>
                    <input type="password" name="password" required minlength="6">
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%; padding:14px; margin-top:10px;">
                    Registriraj se
                </button>
            </form>
        </div>
    </div>

    <script>
        function openModal(id) {
            document.getElementById(id).style.display = 'block';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        function setRole(role) {
            document.getElementById('roleInput').value = role;
            document.querySelectorAll('.user-type').forEach(el => el.classList.remove('active'));
            document.querySelector(`.user-type[data-type="${role}"]`).classList.add('active');
        }

        // Zatvaranje modala klikom izvan njega
        window.onclick = function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>