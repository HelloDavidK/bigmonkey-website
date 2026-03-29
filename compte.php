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

// CSRF token pour sécuriser les soumissions de formulaires
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// Pré-remplissage email (jamais le mot de passe)
$rememberedEmail = htmlspecialchars($_COOKIE['user_email'] ?? '', ENT_QUOTES, 'UTF-8');
$rememberChecked = isset($_COOKIE['user_email']) ? 'checked' : '';
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
                <form id="form-connexion" class="account-form" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

                    <div class="form-group">
                        <label style="color:#000;" for="login-email">Email</label>
                        <input id="login-email" type="email" name="email" required value="<?php echo $rememberedEmail; ?>" autocomplete="email">
                    </div>

                    <div class="form-group">
                        <label style="color:#000;" for="login-password">Mot de passe</label>
                        <input id="login-password" type="password" name="password" required autocomplete="current-password">

                        <div class="form-footer-options">
                            <label class="remember-me">
                                <input type="checkbox" name="remember" <?php echo $rememberChecked; ?>>
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
                <form id="form-inscription" class="account-form" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

                    <div class="form-group">
                        <label style="color:#000;" for="register-pseudo">Pseudo</label>
                        <input id="register-pseudo" type="text" name="pseudo" required autocomplete="nickname">
                    </div>
                    <div class="form-group">
                        <label style="color:#000;" for="register-email">Email</label>
                        <input id="register-email" type="email" name="email" required autocomplete="email">
                    </div>
                    <div class="form-group">
                        <label style="color:#000;" for="register-phone">Téléphone</label>
                        <input id="register-phone" type="tel" name="phone" placeholder="034..." required autocomplete="tel">
                    </div>
                    <div class="form-group">
                        <label style="color:#000;" for="register-password">Mot de passe</label>
                        <input id="register-password" type="password" name="password" required minlength="8" autocomplete="new-password">
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

    function setButtonState(button, text, disabled = true) {
        button.innerText = text;
        button.disabled = disabled;
    }

    function handleAjaxError(button, defaultText) {
        Swal.fire({
            ...monkeyToast,
            icon: 'error',
            title: 'Erreur réseau',
            text: 'Impossible de contacter le serveur. Veuillez réessayer.'
        });
        setButtonState(button, defaultText, false);
    }

    // LOGIQUE CONNEXION AJAX
    document.getElementById('form-connexion').addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = this.querySelector('button[type="submit"]');
        setButtonState(btn, 'Vérification...');

        const formData = new FormData(this);

        fetch('login_process.php', { method: 'POST', body: formData })
            .then(res => {
                if (!res.ok) {
                    throw new Error('HTTP ' + res.status);
                }
                return res.text();
            })
            .then(data => {
                if (data.trim() === 'success') {
                    return Swal.fire({
                        ...monkeyToast,
                        icon: 'success',
                        title: 'Ravi de vous revoir !',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.href = 'index.php';
                    });
                }

                Swal.fire({ ...monkeyToast, icon: 'warning', title: 'Erreur', text: data });
                setButtonState(btn, 'Se connecter', false);
            })
            .catch(() => {
                handleAjaxError(btn, 'Se connecter');
            });
    });

    // LOGIQUE INSCRIPTION AJAX
    document.getElementById('form-inscription').addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = this.querySelector('button[type="submit"]');
        setButtonState(btn, 'Chargement...');

        const formData = new FormData(this);

        fetch('inscription.php', { method: 'POST', body: formData })
            .then(res => {
                if (!res.ok) {
                    throw new Error('HTTP ' + res.status);
                }
                return res.text();
            })
            .then(data => {
                if (data.trim() === 'success') {
                    return Swal.fire({
                        ...monkeyToast,
                        icon: 'success',
                        title: '🦍 Bienvenue dans la Tribu !',
                        text: 'Votre compte a été créé avec succès.'
                    }).then(() => {
                        window.location.href = 'index.php';
                    });
                }

                Swal.fire({ ...monkeyToast, icon: 'error', title: 'Oups...', text: data });
                setButtonState(btn, 'Créer mon compte', false);
            })
            .catch(() => {
                handleAjaxError(btn, 'Créer mon compte');
            });
    });
</script>

</body>
</html>
