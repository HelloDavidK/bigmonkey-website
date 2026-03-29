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
        /* Petit ajout pour s'assurer que le dropdown s'affiche bien */
        .user-dropdown {
            display: none;
            position: absolute;
            background: #1a1a1a;
            min-width: 160px;
            box-shadow: 0px 8px 16px rgba(0,0,0,0.5);
            z-index: 1000;
            border: 1px solid #333;
            top: 100%;
            right: 0;
        }
        .user-dropdown.show { display: block; }
        .user-dropdown a {
            color: white;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            font-size: 0.9rem;
        }
        .user-dropdown a:hover { background: #ffcc00; color: #000; }
        .user-menu-container { position: relative; display: inline-block; }
        .user-welcome { color: #ffcc00; font-weight: bold; }
    </style>
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
                            <a href="https://wa.me/261340154055" target="_blank"><img src="img/whatsapp-icon.svg" alt="WhatsApp"></a>
                            <a href="https://www.facebook.com/profile.php?id=61578973073407" target="_blank"><img src="img/facebook-icon.svg" alt="Facebook"></a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="top-bar-right">
                <?php if(isset($_SESSION['pseudo'])): ?>
                    <div class="user-menu-container">
                        <span class="user-welcome" id="user-top-zone" style="cursor: pointer;">
                            <i class="fas fa-user-circle"></i> Salut, <strong><?php echo htmlspecialchars($_SESSION['pseudo']); ?></strong> <i class="fas fa-chevron-down" style="font-size: 0.8em;"></i>
                        </span>
                        <div class="user-dropdown">
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
        </div> <div class="logo">
            <a href="index.php">
                <img src="img/logo.png" alt="Big Monkey Logo">
            </a>
        </div> <div class="header-right">
            <div class="mobile-search-icon" id="mobile-search-btn">
                <i class="fas fa-search"></i>
            </div>
            <a href="panier.php" class="cart-wrapper">
                <div class="cart-icon-container">
                    <i class="fa-solid fa-bag-shopping"></i> 
                    <span class="cart-badge-new"><?php echo $cart_count; ?></span>
                </div>
            </a>
        </div> </div> <div class="mobile-search-bar" id="mobile-search-bar">
        <form action="recherche.php" method="GET">
            <input type="text" name="q" placeholder="Rechercher..." autocomplete="off">
            <button type="submit"><i class="fas fa-search"></i></button>
        </form>
    </div>

</div>
    <div class="mobile-search-bar" id="mobile-search-bar">
        <form action="recherche.php" method="GET">
            <input type="text" name="q" placeholder="Rechercher un produit..." autocomplete="off">
            <button type="submit"><i class="fas fa-search"></i></button>
        </form>
    </div>
</div>
    </div>
<nav class="desktop-menu">
    <ul>
        <li class="has-mega-menu">
            <a href="#">E-LIQUIDES <span class="menu-arrow">▼</span></a>
            <div class="mega-menu">
                <div class="mega-container">
                    <div class="mega-col">
                        <h3>PAR MARQUES</h3>
                        <div class="icon-grid">
                            <a href="#" class="icon-item"><img src="img/fruizee-icon.jpg" alt="Fruizee Madagascar"></a>
                            <a href="#" class="icon-item"><img src="img/e.tasty-icon.jpg" alt="E.Tasty"></a>
                            <a href="#" class="icon-item"><img src="img/swoke-icon.jpg" alt="Swoke"></a>
                            <a href="#" class="icon-item"><img src="img/tjuice-icon.jpg" alt="T-Juice"></a>
                            <a href="#" class="icon-item"><img src="img/le coq qui vape-icon.jpg" alt="Le Coq Qui Vape"></a>
                            <a href="#" class="icon-item"><img src="img/le french liquid-icon.jpg" alt="Le French Liquid"></a>
                        </div>
                        <a href="marques.html" class="all-link">Voir toutes les marques</a>
                    </div>
                    <div class="mega-col">
                        <h3>PAR CONTENANCES</h3>
                        <div class="icon-grid text-grid">
                            <a href="#" class="icon-item"><img src="img/10ml-icon.svg" alt="E-liquide 10ml"><span>10ml</span></a>
                            <a href="#" class="icon-item"><img src="img/50ml-icon.svg" alt="E-liquide 50ml"><span>50ml</span></a>
                            <a href="#" class="icon-item"><img src="img/100ml-icon.svg" alt="E-liquide 100ml"><span>100ml</span></a>
                        </div>
                    </div>
                    <div class="mega-col">
                        <h3>PAR SAVEURS</h3>
                        <div class="icon-grid text-grid">
                            <a href="#" class="icon-item"><img src="img/classic-icon.svg" alt="Saveur Classic"><span>Classic</span></a>
                            <a href="#" class="icon-item"><img src="img/gourmand-icon.svg" alt="Saveur Gourmande"><span>Gourmand</span></a>
                            <a href="#" class="icon-item"><img src="img/fruite-icon.svg" alt="Saveur Fruitée"><span>Fruité</span></a>
                            <a href="#" class="icon-item"><img src="img/fruité frais-icon.svg" alt="Saveur Fraîche"><span>Fruité Frais</span></a>
                            <a href="#" class="icon-item"><img src="img/mentholé-icon.svg" alt="Saveur Menthe"><span>Mentholé</span></a>
                            <a href="#" class="icon-item"><img src="img/boisson-icon.svg" alt="Saveur Boisson"><span>Boisson</span></a>
                        </div>
                    </div>
                </div>
            </div>
        </li>

        <li class="has-mega-menu">
            <a href="#">DIY <span class="menu-arrow">▼</span></a>
            <div class="mega-menu">
                <div class="mega-container">
                    <div class="mega-col">
                        <h3>ACCESSOIRES DIY</h3>
                        <div class="icon-grid text-grid">
                            <a href="#" class="icon-item"><img src="img/concentré-icon.svg" alt="Concentrés DIY"><span>Concentrés</span></a>
                            <a href="#" class="icon-item"><img src="img/Materiel DIY-icon.svg" alt="Matériels DIY"><span>Matériels DIY</span></a>
                            <a href="#" class="icon-item"><img src="img/additif-icon.svg" alt="Additifs DIY"><span>Additifs</span></a>
                            <a href="#" class="icon-item"><img src="img/base-icon.svg" alt="Bases e-liquide"><span>Bases</span></a>
                            <a href="#" class="icon-item"><img src="img/booster-icon.svg" alt="Boosters Nicotine"><span>Boosters</span></a>
                        </div>
                    </div>
                    <div class="mega-col">
                        <h3>NOS E-LIQUIDES DIY</h3>
                        <div class="icon-grid text-grid">
                            <a href="#" class="icon-item"><img src="img/10ml DIY-icon.svg" alt="DIY 10ml"><span>10 ML</span></a>
                            <a href="#" class="icon-item"><img src="img/30ml DIY-icon.svg" alt="DIY 30ml"><span>30 ML</span></a>
                            <a href="#" class="icon-item"><img src="img/50ml DIY-icon.svg" alt="DIY 50ml"><span>50 ML</span></a>
                        </div>
                    </div>
                </div>
            </div>
        </li>

        <li class="has-mega-menu">
            <a href="#">E-CIGARETTES <span class="menu-arrow">▼</span></a>
            <div class="mega-menu">
                <div class="mega-container">
                    <div class="mega-col">
                        <h3>KITS & BATTERIE</h3>
                        <div class="icon-grid text-grid">
                            <a href="#" class="icon-item"><img src="img/Kits complet-icon.svg" alt="Kits complets"><span>Kits complets</span></a>
                            <a href="#" class="icon-item"><img src="img/pod-icon.svg" alt="Pods"><span>Pods</span></a>
                            <a href="#" class="icon-item"><img src="img/box et batterie-icon.svg" alt="Box & Batteries"><span>Box & Batteries</span></a>
                            <a href="#" class="icon-item"><img src="img/puff-icon.svg" alt="Puffs"><span>Puffs</span></a>
                        </div>
                    </div>
                    <div class="mega-col">
                        <h3>CLEAROS & ATOS</h3>
                        <div class="icon-grid text-grid">
                            <a href="#" class="icon-item"><img src="img/clearos-icon.svg" alt="Clearomiseurs"><span>Clearos</span></a>
                            <a href="#" class="icon-item"><img src="img/Atos-icon.svg" alt="Atomiseurs"><span>Atos</span></a>
                        </div>
                    </div>
                    <div class="mega-col">
                        <h3>CARTOUCHES & RÉSISTANCES</h3>
                        <div class="icon-grid text-grid">
                            <a href="#" class="icon-item"><img src="img/cartouche-icon.svg" alt="Cartouches"><span>Cartouches</span></a>
                            <a href="#" class="icon-item"><img src="img/resistance-icon.svg" alt="Résistances"><span>Résistances</span></a>
                        </div>
                    </div>
                </div>
            </div>
        </li>

        <li><a href="#">NOUVEAUTÉS</a></li>
        <li class="promo"><a href="#">BONS PLANS</a></li>
        
        <li class="menu-search-container">
            <form action="/search" method="get">
                <input type="text" name="q" placeholder="Recherche...">
                <button type="submit" class="search-btn">🔍</button>
            </form>
        </li>
    </ul>
</nav>  
    </div>
</header>

<div class="mobile-drawer" id="mobile-drawer">
    <div class="drawer-header">
        <span>MENU</span>
        <span class="close-drawer">&times;</span>
    </div>
    
    <ul>
        <li>
            <div class="mobile-menu-item">
                <a href="#">E-LIQUIDES</a>
                <span class="toggle-submenu">+</span>
            </div>
            <ul class="submenu-mobile">
                <li>
                    <div class="mobile-menu-item nested">
                        <a href="#">Par marques</a>
                        <span class="toggle-submenu">+</span>
                    </div>
                    <ul class="submenu-mobile">
                        <li><a href="#">Aromazon</a></li>
                        <li><a href="#">Arômes & Liquides</a></li>
                        <li><a href="#">Avap</a></li>
                        <li><a href="#">Ben Northon</a></li>
                        <li><a href="#">D'Lice</a></li>
                        <li><a href="#">Eliquid France</a></li>
                        <li><a href="#">E.Tasty</a></li>
                        <li><a href="#">Fcukin'Flava</a></li>
                        <li><a href="#">Kyandi Shop</a></li>
                        <li><a href="#">La Fabrique Française</a></li>
                        <li><a href="#">Le Coq qui vape</a></li>
                        <li><a href="#">Le French Liquide</a></li>
                        <li><a href="#">Liquid Arom</a></li>
                        <li><a href="#">Liquideo</a></li>
                        <li><a href="#">Maison Fuel</a></li>
                        <li><a href="#">Medusa Juice</a></li>
                        <li><a href="#">Mon chou choux</a></li>
                        <li><a href="#">Secret's Lab</a></li>
                        <li><a href="#">Swoke</a></li>
                        <li><a href="#">T-Juice</a></li>
                        <li><a href="#">Vampire Vape</a></li>
                        <li><a href="#">Vape Maker</a></li>
                        <li><a href="#">X-Bar</a></li>
                    </ul>
                </li>
                <li>
                    <div class="mobile-menu-item nested">
                        <a href="#">Par contenances</a>
                        <span class="toggle-submenu">+</span>
                    </div>
                    <ul class="submenu-mobile">
                        <li><a href="#">10 ML</a></li>
                        <li><a href="#">50 ML</a></li>
                        <li><a href="#">100 ML</a></li>
                    </ul>
                </li>
                <li>
                    <div class="mobile-menu-item nested">
                        <a href="#">Par saveurs</a>
                        <span class="toggle-submenu">+</span>
                    </div>
                    <ul class="submenu-mobile">
                        <li><a href="#">Classic</a></li>
                        <li><a href="#">Gourmand</a></li>
                        <li><a href="#">Fruité</a></li>
                        <li><a href="#">Fruité Frais</a></li>
                        <li><a href="#">Mentholé</a></li>
                        <li><a href="#">Boisson</a></li>
                    </ul>
                </li>
            </ul>
        </li>

        <li>
            <div class="mobile-menu-item">
                <a href="#">DIY</a>
                <span class="toggle-submenu">+</span>
            </div>
            <ul class="submenu-mobile">
                <li>
                    <div class="mobile-menu-item nested">
                        <a href="#">Accessoires DIY</a>
                        <span class="toggle-submenu">+</span>
                    </div>
                    <ul class="submenu-mobile">
                        <li><a href="#">Concentrés</a></li>
                        <li><a href="#">Matériels DIY</a></li>
                        <li><a href="#">Additifs</a></li>
                        <li><a href="#">Bases</a></li>
                        <li><a href="#">Boosters</a></li>
                    </ul>
                </li>
                <li>
                    <div class="mobile-menu-item nested">
                        <a href="#">Nos e-liquides DIY</a>
                        <span class="toggle-submenu">+</span>
                    </div>
                    <ul class="submenu-mobile">
                        <li><a href="#">10 ML</a></li>
                        <li><a href="#">30 ML</a></li>
                        <li><a href="#">50 ML</a></li>
                    </ul>
                </li>
            </ul>
        </li>

        <li>
            <div class="mobile-menu-item">
                <a href="#">E-CIGARETTES</a>
                <span class="toggle-submenu">+</span>
            </div>
            <ul class="submenu-mobile">
                <li>
                    <div class="mobile-menu-item nested">
                        <a href="#">Kits & Batterie</a>
                        <span class="toggle-submenu">+</span>
                    </div>
                    <ul class="submenu-mobile">
                        <li><a href="#">Kits complets</a></li>
                        <li><a href="#">Pods</a></li>
                        <li><a href="#">Box & Batteries</a></li>
                        <li><a href="#">Puffs</a></li>
                    </ul>
                </li>
                <li>
                    <div class="mobile-menu-item nested">
                        <a href="#">Clearos & Atos</a>
                        <span class="toggle-submenu">+</span>
                    </div>
                    <ul class="submenu-mobile">
                        <li><a href="#">Clearos</a></li>
                        <li><a href="#">Atos</a></li>
                    </ul>
                </li>
                <li>
                    <div class="mobile-menu-item nested">
                        <a href="#">Cartouches & Résistances</a>
                        <span class="toggle-submenu">+</span>
                    </div>
                    <ul class="submenu-mobile">
                        <li><a href="#">Cartouches</a></li>
                        <li><a href="#">Résistances</a></li>
                    </ul>
                </li>
            </ul>
        </li>

        <li><a href="#"><span>NOUVEAUTÉS</span></a></li>
        <li class="promo-mob"><a href="#"><span>BONS PLANS</span></a></li>

        <div class="mobile-contact-section">
            <h3>Nous contacter</h3>
            <a href="mailto:mdgpav@gmail.com" class="mobile-contact-item">
                <img src="img/gmail-icon.svg" alt="Gmail">
                <span>mdgpav@gmail.com</span>
            </a>
            <a href="https://wa.me/261340154055" target="_blank" class="mobile-contact-item">
                <img src="img/whatsapp-icon.svg" alt="WhatsApp">
                <span>+261 34 01 540 55</span>
            </a>
            <a href="https://www.facebook.com/profile.php?id=61578973073407" target="_blank" class="mobile-contact-item">
                <img src="img/facebook-icon.svg" alt="Facebook">
                <span>PAV MDG</span>
            </a>
        </div>
<div class="mobile-auth-section" style="padding: 20px; border-top: 1px solid #333; margin-top: 20px; background: #000;">
            <?php if(isset($_SESSION['pseudo'])): ?>
                
                <a href="profil.php" style="display: block; margin-bottom: 15px; text-decoration: none; color: white; border: 1px solid #fff; padding: 12px; border-radius: 30px; text-align: center; font-size: 14px; font-weight: bold;">
                    <i class="fas fa-user-cog"></i> MON PROFIL
                </a>

                <a href="commandes.php" style="display: block; margin-bottom: 15px; text-decoration: none; color: white; border: 1px solid #fff; padding: 12px; border-radius: 30px; text-align: center; font-size: 14px; font-weight: bold;">
                    <i class="fas fa-box"></i> MES COMMANDES
                </a>

                <a href="logout.php" style="display: block; text-decoration: none; color: #ff4444; border: 1px solid #ff4444; padding: 12px; border-radius: 30px; text-align: center; font-size: 14px; font-weight: bold;">
                    <i class="fas fa-sign-out-alt"></i> SE DÉCONNECTER
                </a>

            <?php else: ?>
                <a href="compte.php" style="display: block; text-decoration: none; color: white; border: 1px solid #fff; padding: 15px; border-radius: 30px; text-align: center; font-size: 14px; font-weight: bold;">
                    <i class="fas fa-user"></i> CONNEXION / INSCRIPTION
                </a>
            <?php endif; ?>
        </div>
    </ul>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const userTrigger = document.getElementById('user-top-zone');
    const userDropdown = document.querySelector('.user-dropdown');

    if (userTrigger && userDropdown) {
        userTrigger.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('show');
        });

        document.addEventListener('click', function() {
            userDropdown.classList.remove('show');
        });
    }
});
document.addEventListener('DOMContentLoaded', function() {
    // Sélection des flèches ou des liens de menus ayant des sous-menus
    const menuItems = document.querySelectorAll('.desktop-menu li.has-children > a, .menu-arrow');

    menuItems.forEach(item => {
        item.addEventListener('click', function(e) {
            // Empêche la navigation si on clique sur la flèche pour ouvrir le menu
            e.preventDefault();
            e.stopPropagation();

            const parentLi = this.closest('li');
            
            // Ferme les autres menus ouverts
            document.querySelectorAll('.desktop-menu li.active').forEach(activeLi => {
                if (activeLi !== parentLi) {
                    activeLi.classList.remove('active');
                }
            });

            // Alterne l'affichage du menu actuel
            parentLi.classList.toggle('active');
        });
    });

    // Ferme le mega menu si on clique n'importe où ailleurs sur la page
    document.addEventListener('click', function() {
        document.querySelectorAll('.desktop-menu li.active').forEach(li => {
            li.classList.remove('active');
        });
    });
});
</script>