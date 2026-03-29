<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'config.php';

// Si l'utilisateur est déjà connecté, on le redirige directement vers l'accueil
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Compte - Big Monkey Madagascar</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        .account-section { padding: 60px 0; background: #121212; min-height: 70vh; }
        .account-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; max-width: 1000px; margin: 0 auto; }
        .account-box { background: #1a1a1a; padding: 40px; border-radius: 4px; border: 1px solid #333; }
        .account-box.highlight { background: #ffcc00; border: none; }
        .account-box h3 { font-family: 'Arial Black', sans-serif; text-transform: uppercase; margin-bottom: 25px; font-size: 1.5rem; }
        
        /* Correction du cadrage des inputs */
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; }
        .form-group input { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #444; 
            background: #222; 
            color: #fff; 
            border-radius: 4px; 
            box-sizing: border-box;
        }
        /* Style spécifique pour le formulaire blanc (Déjà client) */
        .box-white input { background: #f4f4f4; color: #000; border: 1px solid #ccc; }
        
        .form-footer-options { display: flex; justify-content: space-between; align-items: center; margin-top: 12px; font-size: 0.85rem; }
        .remember-me { display: flex; align-items: center; gap: 8px; cursor: pointer; color: #000; }
        .remember-me input { width: auto !important; margin: 0; cursor: pointer; }
        .forgot-pass { color: #555; text-decoration: none; font-weight: bold; }
        .forgot-pass:hover { color: #ffcc00; text-decoration: underline; }

        @media (max-width: 768px) {
            .account-grid { grid-template-columns: 1fr; padding: 0 20px; }
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<section class="account-section">
    <div class="container">
        <div class="account-grid">
            <div class="account-box box-white" style="background: #fff;"> 
                <h3 style="color:#000;">Déjà client ?</h3>
                <form id="form-connexion" class="account-form">
                    <div class="form-group">
                        <label style="color:#000;">Email</label>
                        <input type="email" name="email" required 
                               value="<?php echo $_COOKIE['user_email'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label style="color:#000;">Mot de passe</label>
                        <input type="password" name="password" required 
                               value="<?php echo $_COOKIE['user_password'] ?? ''; ?>">
                        
                        <div class="form-footer-options">
                            <label class="remember-me">
                                <input type="checkbox" name="remember" <?php echo isset($_COOKIE['user_email']) ? 'checked' : ''; ?>> 
                                Se souvenir de moi
                            </label>
                            <a href="recuperation.php" class="forgot-pass">Mot de passe oublié ?</a>
                        </div>
                    </div>

                    <button type="submit" class="btn-account" style="background:#ffcc00; color:#000; width: 100%; border: none; padding: 15px; cursor: pointer; font-weight: bold; text-transform: uppercase; margin-top: 15px;">
                        Se connecter
                    </button>
                </form>
            </div>

            <div class="account-box highlight">
                <h3 style="color:#000;">Nouveau ici ?</h3>
                <div class="account-benefits">
                    <p style="color:#000;">*Avoir un profil Big Monkey vous permettra de profiter de toutes nos offres exclusives !</p><br>
                </div>
                <form id="form-inscription" class="account-form">
                    <div class="form-group">
                        <label style="color:#000;">Pseudo</label>
                        <input type="text" name="pseudo" required>
                    </div>
                    <div class="form-group">
                        <label style="color:#000;">Email</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label style="color:#000;">Téléphone</label>
                        <input type="tel" name="phone" placeholder="034..." required>
                    </div>
                    <div class="form-group">
                        <label style="color:#000;">Mot de passe</label>
                        <input type="password" name="password" required>
                    </div>
                    <button type="submit" class="btn-account" style="background:#000; color:#fff; width: 100%; border: none; padding: 15px; cursor: pointer; font-weight: bold; text-transform: uppercase; margin-top: 20px;">
                        Créer mon compte
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>

<script>
    const monkeyToast = {
        background: '#1a1a1a',
        color: '#ffffff',
        confirmButtonColor: '#ffcc00'
    };

    // LOGIQUE CONNEXION AJAX
    document.getElementById('form-connexion').addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = this.querySelector('button');
        btn.innerText = 'Vérification...';
        btn.disabled = true;

        const formData = new FormData(this);
        fetch('login_process.php', { method: 'POST', body: formData })
        .then(res => res.text())
        .then(data => {
            if(data.trim() === 'success') {
                Swal.fire({
                    ...monkeyToast,
                    icon: 'success',
                    title: 'Ravi de vous revoir !',
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    window.location.href='index.php';
                });
            } else {
                Swal.fire({ ...monkeyToast, icon: 'warning', title: 'Erreur', text: data });
                btn.innerText = 'Se connecter';
                btn.disabled = false;
            }
        });
    });

    // LOGIQUE INSCRIPTION AJAX
    document.getElementById('form-inscription').addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = this.querySelector('button');
        btn.innerText = 'Chargement...';
        btn.disabled = true;

        const formData = new FormData(this);
        fetch('inscription.php', { method: 'POST', body: formData })
        .then(res => res.text())
        .then(data => {
            if(data.trim() === 'success') { 
                Swal.fire({
                    ...monkeyToast,
                    icon: 'success',
                    title: '🦍 Bienvenue dans la Tribu !',
                    text: 'Votre compte a été créé avec succès.'
                }).then(() => { window.location.href='index.php'; });
            } else { 
                Swal.fire({ ...monkeyToast, icon: 'error', title: 'Oups...', text: data });
                btn.innerText = 'Créer mon compte';
                btn.disabled = false;
            }
        });
    });
</script>

</body>
</html>