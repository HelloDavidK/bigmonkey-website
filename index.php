<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';
require_once 'collection_helpers.php';

$homePromotions = fetchHomeSectionProducts($pdo, 'promotions', 4);
$homePepitesEliquides = fetchHomeSectionProducts($pdo, 'pepite', 8, 'eliquides');
$homePepitesDiy = fetchHomeSectionProducts($pdo, 'pepite', 8, 'diy');
$homePepitesMateriel = fetchHomeSectionProducts($pdo, 'pepite', 8, 'ecigarettes');
$homePuffSelection = fetchHomeSectionProducts($pdo, 'puff_selection', 4, 'puffs');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Big Monkey - Vape Shop Madagascar</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<?php include 'header.php'; ?>
<?php include 'add_to_cart_form.php'; ?>

<main>
<div class="banner-container">
    <?php if(!isset($_SESSION['user_id'])): ?>
        
        <a href="compte.php" class="banner-link banner-desktop">
            <img src="img/banière image.jpg" alt="Rejoignez Big Monkey">
        </a>
        <a href="compte.php" class="banner-link banner-mobile">
            <img src="img/baniere-mobile.jpg" alt="Rejoignez Big Monkey Mobile">
        </a>

    <?php else: ?>

        <div class="slider-wrapper">
            <div class="slides">
                
                <div class="slide">
                    <a href="collection.php" class="banner-link banner-desktop">
                        <img src="img/slider1-pc.jpg" alt="Nouveautés E-liquides">
                    </a>
                    <a href="collection.php" class="banner-link banner-mobile">
                        <img src="img/slider1-mobile.jpg" alt="Nouveautés E-liquides Mobile">
                    </a>
                </div>

                <div class="slide">
                    <a href="collection.php" class="banner-link banner-desktop">
                        <img src="img/slider2-pc.jpg" alt="Promotions Matériel">
                    </a>
                    <a href="collection.php" class="banner-link banner-mobile">
                        <img src="img/slider2-mobile.jpg" alt="Promotions Matériel Mobile">
                    </a>
                </div>

            </div>
            
            <div class="slider-dots">
                <span class="dot active"></span>
                <span class="dot"></span>
            </div>
        </div>

    <?php endif; ?>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    const slides = document.querySelectorAll('.slide');
    const dots = document.querySelectorAll('.dot');
    let currentSlide = 0;

    if (slides.length > 0) {
        setInterval(() => {
            slides[currentSlide].classList.remove('active');
            dots[currentSlide].classList.remove('active');
            
            currentSlide = (currentSlide + 1) % slides.length;
            
            // Logique de défilement simplifiée (ou utilisez transform: translateX)
            const container = document.querySelector('.slides');
            container.style.transform = `translateX(-${currentSlide * 100}%)`;
            
            dots[currentSlide].classList.add('active');
        }, 4000); // 4 secondes
    }
});
</script>
</div>
<div class="promo-bar-container">
    <div class="promo-bar-content">
        10% DE REMISE SUR VOTRE PREMIÈRE COMMANDE
    </div>
</div>
<section class="demo-assurance-compact">
    <div class="slider-container-wrapper">
        <button class="slider-arrow prev" onclick="moveSlide(-1)">&#10094;</button>

        <div class="slider-track">
            <div class="demo-item-compact">
                <img src="img/produit certifié-icon.svg" alt="Authentique">
                <div class="text-wrapper-compact">
                    <h3>100% AUTHENTIQUE</h3>
                    <p>Fioles premium certifiées importées de France.</p>
                </div>
            </div>
            
            <div class="demo-item-compact">
                <img src="img/livraison-icon.svg" alt="Livraison">
                <div class="text-wrapper-compact">
                    <h3>LIVRAISON EXPRESS</h3>
                    <p>Le jour même sur Antananarivo.</p>
                </div>
            </div>

            <div class="demo-item-compact">
                <img src="img/paiement-icon.svg" alt="Paiement">
                <div class="text-wrapper-compact">
                    <h3>PAIEMENT FLEXIBLE</h3>
                    <p>MVola, Orange Money, Airtel Money & Cash.</p>
                </div>
            </div>
        </div>

        <button class="slider-arrow next" onclick="moveSlide(1)">&#10095;</button>
    </div>
</section>
<section class="promotions-section">
    <div class="container">
        <div class="section-header">
            <h2 class="title-main">Nos <span>Promotions</span> 🔥</h2>
            <p>Profitez des meilleures offres du moment sur une sélection de matériels et e-liquides.</p>
        </div>

        <div class="promo-grid">
            <?php if (!empty($homePromotions)): ?>
                <?= renderHomePromoCards($homePromotions); ?>
            <?php else: ?>
                <p style="grid-column: 1 / -1; text-align:center;">Aucune promotion disponible pour le moment.</p>
            <?php endif; ?>
        </div>
    </div>
</section>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const cards = document.querySelectorAll('.promo-card');

    cards.forEach(card => {
        const oldPriceEl = card.querySelector('.old-price');
        const promoPriceEl = card.querySelector('.promo-val');
        const badge = card.querySelector('.promo-badge');

        if (oldPriceEl && promoPriceEl && badge) {
            const oldPrice = parseFloat(oldPriceEl.textContent.replace(/[^\d]/g, ''));
            const promoPrice = parseFloat(promoPriceEl.textContent.replace(/[^\d]/g, ''));

            if (oldPrice > 0 && promoPrice < oldPrice) {
                // Calcul du pourcentage réel
                const realDiscount = ((oldPrice - promoPrice) / oldPrice) * 100;
                
                // ARRONDIE À LA DIZAINE SUPÉRIEURE (ex: 27% -> 30%)
                const roundedDiscount = Math.ceil(realDiscount / 10) * 10;
                
                badge.textContent = `-${roundedDiscount}%`;
                badge.style.display = 'block';
            } else {
                badge.style.display = 'none';
            }
        }
    });
});
</script>
</section>
<section class="cat-grid-container">
    <h2 class="section-title">NOS UNIVERS VAPE</h2>
    
    <div class="cat-grid">
        <a href="collection.php?cat=eliquides" class="cat-card">
            <div class="cat-image-wrapper">
                <img src="img/cat-eliquide.jpg" alt="E-liquides certifiés France - Big Monkey">
            </div>
            <div class="cat-info">
                <h3>E-LIQUIDES PREMIUM</h3>
                <span>Découvrir la collection →</span>
            </div>
        </a>

        <a href="collection.php?cat=diy" class="cat-card">
            <div class="cat-image-wrapper">
                <img src="img/cat-diy.jpg" alt="Arômes et Bases DIY Madagascar">
            </div>
            <div class="cat-info">
                <h3>L'UNIVERS DU DIY</h3>
                <span>Créez vos mélanges →</span>
            </div>
        </a>

        <a href="collection.php?cat=ecigarettes" class="cat-card">
            <div class="cat-image-wrapper">
                <img src="img/cat-materiel.jpg" alt="Cigarettes électroniques et Pods Antananarivo">
            </div>
            <div class="cat-info">
                <h3>MATÉRIEL & KITS</h3>
                <span>Trouvez votre vape →</span>
            </div>
        </a>
    </div>
</section>
<section class="best-sellers-section">
    <h2>LES PÉPITES DU MONKEY</h2>
    
    <div class="filter-buttons">
        <button class="filter-btn active" data-category="e-liquides" onclick="filterProducts('e-liquides', event)">E-Liquides</button>
        <button class="filter-btn" data-category="diy" onclick="filterProducts('diy', event)">L'Univers du DIY</button>
        <button class="filter-btn" data-category="materiel" onclick="filterProducts('materiel', event)">Matériel & Kits</button>
    </div>
<div class="product-grid" id="product-grid">

    <?= renderHomePepiteCards($homePepitesEliquides, 'e-liquides'); ?>
    <?= renderHomePepiteCards($homePepitesDiy, 'diy'); ?>
    <?= renderHomePepiteCards($homePepitesMateriel, 'materiel'); ?>

</div>
</section>

<section class="section-avantages-monkey">
    <div class="avantages-grid">
        <div class="advantage-card">
            <span class="icon">🚀</span>
            <h4>Livraison Express</h4>
            <p>Antananarivo et provinces (Cotisse, EMS...) Commandez avant 12h, recevez le jour même sur Tana.</p>
        </div>
        <div class="advantage-card">
            <span class="icon">🛡️</span>
            <h4>100% Authentique</h4>
            <p>Produits certifiés directs constructeurs. Fini les contrefaçons. Matériels et e-liquides vérifiés.</p>
        </div>
        <div class="advantage-card">
            <span class="icon">💬</span>
            <h4>Conseils d'Experts</h4>
            <p>Nous vous accompagnons dans votre sevrage. Notre collectif d'experts est disponible par WhatsApp.</p>
        </div>
    </div>
</section>
<section class="selection-section">
    <div class="container">
        <div class="section-header">
            <h2 class="title-main">Notre <span>Sélection Puff</span> ✨</h2>
            <p>Découvrez nos Puffs incontournables et les dernières nouveautés disponibles à Antananarivo.</p>
        </div>

        <div class="promo-grid">
            <?php if (!empty($homePuffSelection)): ?>
                <?= renderHomePromoCards($homePuffSelection); ?>
            <?php else: ?>
                <p style="grid-column: 1 / -1; text-align:center;">Aucune puff en sélection pour le moment.</p>
            <?php endif; ?>
        </div>
    </div>
</section>
<section class="blog-section">
    <div class="container">
        <div class="section-header">
            <h2 class="title-main">Le <span>Blog</span> 📖</h2>
            <p>Conseils d'experts et actus vape à Madagascar.</p>
        </div>

        <div class="blog-container-grid">
            <div class="blog-block">
                <div class="blog-img">
                    <img src="img/blog-puff-32k.jpg" alt="Puff Fighter X 32K">
                </div>
                <div class="blog-info">
                    <span class="blog-tag">Innovation</span>
                    <h3>Puff 32.000 bouffées : Le test complet</h3>
                    <p>On a testé la Fighter X de Maison Fuel. Est-ce vraiment rentable ?</p>
                    <a href="index.php" class="blog-link">Lire la suite</a>
                </div>
            </div>

            <div class="blog-block">
                <div class="blog-img">
                    <img src="img/blog-entretien.jpg" alt="Entretien Pod VPrime">
                </div>
                <div class="blog-info">
                    <span class="blog-tag">Conseils</span>
                    <h3>Comment entretenir son Kit VPrime ?</h3>
                    <p>4 astuces simples pour prolonger la durée de vie de votre batterie OXVA.</p>
                    <a href="index.php" class="blog-link">Lire la suite</a>
                </div>
            </div>

            <div class="blog-block">
                <div class="blog-img">
                    <img src="img/blog-saveurs-just.jpg" alt="Saveurs Just">
                </div>
                <div class="blog-info">
                    <span class="blog-tag">Saveurs</span>
                    <h3>Top 5 des e-liquides "Just" cet été</h3>
                    <p>Du Citron au Café-Biscuit, découvrez les favoris de nos clients.</p>
                    <a href="index.php" class="blog-link">Lire la suite</a>
                </div>
            </div>
        </div>
        <div class="blog-footer">
            <a href="index.php" class="btn-load-more">Voir toute l'actualité</a>
        </div>
    </div>
</section>
</main>
<?php include 'footer.php'; ?>
    <script src="js/script.js"></script>
<script>
    const nomSauvegarde = localStorage.getItem('clientNom');
    const topBarAuthLink = document.querySelector('.top-bar-right a');
    if (nomSauvegarde && topBarAuthLink) {
        topBarAuthLink.innerHTML = `<i class="fas fa-user"></i> ${nomSauvegarde}`;
    }
</script>
</body>
</html>
