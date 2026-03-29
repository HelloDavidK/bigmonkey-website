<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php'; // Utilisation de require_once pour plus de sécurité

// Simulation du compteur panier
$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Big Monkey - Expert en E-Liquides & Matériel à Madagascar</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* --- FIX VISIBILITÉ BULLE UTILISATEUR --- */
        .user-menu-container {
            position: relative;
            display: inline-block;
        }

        .user-dropdown {
            display: none;
            position: absolute;
            background-color: #1a1a1a !important; /* Fond noir monkey */
            min-width: 180px;
            box-shadow: 0px 8px 16px rgba(0,0,0,0.6);
            z-index: 9999;
            border: 1px solid #333;
            top: 100%;
            right: 0;
            margin-top: 10px;
            border-radius: 4px;
            padding: 5px 0;
        }

        /* La flèche de la bulle */
        .user-dropdown::before {
            content: "";
            position: absolute;
            bottom: 100%;
            right: 15px;
            border-width: 8px;
            border-style: solid;
            border-color: transparent transparent #1a1a1a transparent;
        }

        .user-dropdown.show {
            display: block !important;
        }

        /* Force le texte en blanc et alignement */
        .user-dropdown a {
            color: #ffffff !important; 
            padding: 12px 15px !important;
            text-decoration: none !important;
            display: flex !important;
            align-items: center;
            gap: 10px;
            font-size: 14px !important;
            border-bottom: 1px solid #2a2a2a;
            transition: background 0.3s;
            text-align: left;
        }

        .user-dropdown a:last-child {
            border-bottom: none;
        }

        .user-dropdown a i {
            width: 20px;
            color: #ffcc00; /* Icônes en jaune monkey */
        }

        .user-dropdown a:hover {
            background-color: #333 !important;
            color: #ffcc00 !important;
        }

        .logout-link {
            color: #ff4444 !important; /* Déconnexion en rouge */
        }
    </style>
</head>
<body>

<header class="main-header">
    <div class="announcement-bar">
        <div class="announcement-content">
            <div class="announcement-item">🚀 Commandez avant 12h : Livraison le jour même</div>
            <div class="announcement-item">📦 Après 12h : Livraison à J+1</div>
            <div class="announcement-item">🤝 Paiement à la livraison (Mvola / Orange Money / Espèces)</div>
        </div>
    </div>
    <div class="top-bar">
        <div class="top-bar-content">
            <div class="top-bar-left">
                <div class="contact-wrapper">
                    <a href="#" class="contact-trigger" id="contactTrigger"><i class="fas fa-envelope"></i> Nous contacter</a>
                    <div class="contact-bubble" id="contactBubble">
                        <div class="bubble-arrow"></div>
                        <div class="contact-icons">
                            <a href="mailto:contact@pav-mdg.com"><img src="img/gmail-icon.svg" alt="Gmail"></a>
                            <a href="https://wa.me/261340154055" target="_blank" rel="noopener noreferrer"><img src="img/whatsapp-icon.svg" alt="WhatsApp"></a>
                            <a href="https://www.facebook.com/profile.php?id=61578973073407" target="_blank" rel="noopener noreferrer"><img src="img/facebook-icon.svg" alt="Facebook"></a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="top-bar-right">
                <?php if(isset($_SESSION['pseudo'])): ?>
                    <div class="user-menu-container">
                        <button
                            type="button"
                            class="user-welcome user-trigger-btn"
                            id="user-top-zone"
                            aria-haspopup="true"
                            aria-expanded="false"
                            aria-controls="user-dropdown-menu"
                        >
                            <i class="fas fa-user-circle"></i> Salut, <strong><?php echo htmlspecialchars($_SESSION['pseudo']); ?></strong> <i class="fas fa-chevron-down" style="font-size: 0.8em;"></i>
                        </button>
                        <div class="user-dropdown" id="user-dropdown-menu">
                            <a href="profil.php"><i class="fas fa-user-cog"></i> Mon profil</a>
                            <a href="commandes.php"><i class="fas fa-box"></i> Mes commandes</a>
                            <a href="logout.php" class="logout-link" style="color: #ff4444;"><i class="fas fa-sign-out-alt"></i> Se déconnecter</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="compte.php" class="login-link"><i class="fas fa-user"></i> Connexion / Inscription</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
<div class="header-navigation-wrapper">
    <div class="header-main-layout">
        <div class="header-left">
            <div class="mobile-toggle" id="mobile-menu-btn">
                <span></span><span></span><span></span>
            </div>
        </div>
        <div class="logo">
            <a href="index.php">
                <img src="img/logo.png" alt="Big Monkey Logo">
            </a>
        </div>
        <div class="header-right">
            <div class="mobile-search-icon" id="mobile-search-btn">
                <i class="fas fa-search"></i>
            </div>
            <a href="panier.php" class="cart-wrapper">
                <div class="cart-icon-container">
                    <i class="fa-solid fa-bag-shopping"></i> 
                    <span class="cart-badge-new"><?php echo $cart_count; ?></span>
                </div>
            </a>
        </div>
    </div>

    <div class="mobile-search-bar" id="mobile-search-bar">
        <form action="collection.php" method="GET">
            <input type="text" name="q" placeholder="Rechercher un produit..." autocomplete="off">
            <button type="submit"><i class="fas fa-search"></i></button>
        </form>
    </div>
</div>
<?php include 'menu-desktop.php'; ?>
</header>

<?php include 'menu-mobile.php'; ?>
<script src="js/script.js"></script>
