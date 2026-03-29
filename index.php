<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'config.php';
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
                    <a href="nouveautes.php" class="banner-link banner-desktop">
                        <img src="img/slider1-pc.jpg" alt="Nouveautés E-liquides">
                    </a>
                    <a href="nouveautes.php" class="banner-link banner-mobile">
                        <img src="img/slider1-mobile.jpg" alt="Nouveautés E-liquides Mobile">
                    </a>
                </div>

                <div class="slide">
                    <a href="bons-plans.php" class="banner-link banner-desktop">
                        <img src="img/slider2-pc.jpg" alt="Promotions Matériel">
                    </a>
                    <a href="bons-plans.php" class="banner-link banner-mobile">
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
</section></section>
<section class="promotions-section">
    <div class="container">
        <div class="section-header">
            <h2 class="title-main">Nos <span>Promotions</span> 🔥</h2>
            <p>Profitez des meilleures offres du moment sur une sélection de matériels et e-liquides.</p>
        </div>

        <div class="promo-grid">
            <div class="promo-card">
                <div class="promo-badge"></div> <div class="promo-image">
                    <img src="img/pack-just-doric.jpg" alt="Pack Starter">
                </div>
                <div class="promo-info">
                    <h4>PACK STARTER JUST</h4>
                    <p class="price"><span class="old-price">137.500 Ar</span> <span class="promo-val">100.000 Ar</span></p>
                    <a href="#" class="btn-promo">Voir l'offre</a>
                </div>
            </div>

            <div class="promo-card">
                <div class="promo-badge"></div>
                <div class="promo-image">
                    <img src="img/smart-pack-just.jpg" alt="Smart pack Just">
                </div>
                <div class="promo-info">
                    <h4>SMART PACK JUST 10 ML</h4>
                    <p class="price"><span class="old-price">87.500 Ar</span> <span class="promo-val">55.000 Ar</span></p>
                    <a href="#" class="btn-promo">Voir l'offre</a>
                </div>
            </div>

            <div class="promo-card">
                <div class="promo-badge"></div>
                <div class="promo-image">
                    <img src="img/wonderful-tart-framboise.jpg" alt="Wonderfult Tart Framboise">
                </div>
                <div class="promo-info">
                    <h4>WONDERFUL TART - FRAMBOISE</h4>
                    <p class="price"><span class="old-price">45.000 Ar</span> <span class="promo-val">35.000 Ar</span></p>
                    <a href="#" class="btn-promo">Voir l'offre</a>
                </div>
            </div>

            <div class="promo-card">
                <div class="promo-badge"></div>
                <div class="promo-image">
                    <img src="img/Kit-Pod-VPrime-2600mAh.jpg" alt="Kit Pod VPrime 2600mAh - OXVA">
                </div>
                <div class="promo-info">
                    <h4>KIT POD VPRIME 2600MAH - OXVA</h4>
                    <p class="price"><span class="old-price">175.000 Ar</span> <span class="promo-val">150.000 Ar</span></p>
                    <a href="#" class="btn-promo">Voir l'offre</a>
                </div>
            </div>
        </div>
    </div>
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
        <a href="/e-liquides" class="cat-card">
            <div class="cat-image-wrapper">
                <img src="img/cat-eliquide.jpg" alt="E-liquides certifiés France - Big Monkey">
            </div>
            <div class="cat-info">
                <h3>E-LIQUIDES PREMIUM</h3>
                <span>Découvrir la collection →</span>
            </div>
        </a>

        <a href="/diy" class="cat-card">
            <div class="cat-image-wrapper">
                <img src="img/cat-diy.jpg" alt="Arômes et Bases DIY Madagascar">
            </div>
            <div class="cat-info">
                <h3>L'UNIVERS DU DIY</h3>
                <span>Créez vos mélanges →</span>
            </div>
        </a>

        <a href="/e-cigarettes" class="cat-card">
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
        <button class="filter-btn active" onclick="filterProducts('e-liquides')">E-Liquides</button>
        <button class="filter-btn" onclick="filterProducts('diy')">L'Univers du DIY</button>
        <button class="filter-btn" onclick="filterProducts('materiel')">Matériel & Kits</button>
    </div>

    <div class="product-grid" id="product-grid">
        
        <div class="product-card" data-category="e-liquides">
            <img src="img/nox-chilan-50-ml.jpg" alt="Chilàn NOX">
            <div class="product-info">
                <h3>Chilàn - NOX</h3>
                <span class="product-desc">Un tiramisu crémeux aux notes de café intense et biscuits fondants.</span>
                <p class="price">50.000 Ar</p>
                <button class="add-to-cart">AJOUTER AU PANIER</button>
            </div>
        </div>
        <div class="product-card" data-category="e-liquides">
            <img src="img/fcukin munkey 100 ml.jpg" alt="Fcukin'Munkey - Fcukin Flava">
            <div class="product-info">
                <h3>Fcukin'Munkey - FCUKIN'FLAVA</h3>
                <span class="product-desc">Un mélange frais de melon juteux, de menthe glacée et de bubble-gum sucré.</span>
                <p class="price">60.000 Ar</p>
                <button class="add-to-cart">AJOUTER AU PANIER</button>
            </div>
        </div>

        <div class="product-card" data-category="e-liquides">
            <img src="img/le-cubain-50ml.jpg" alt="Le Cubain">
            <div class="product-info">
                <h3>Le Cubain - AVAP</h3>
                <span class="product-desc">Un cigare cubain riche et profond, aux notes boisées et légèrement épicées.</span>
                <p class="price">40.000 Ar</p>
                <button class="add-to-cart">AJOUTER AU PANIER</button>
            </div>
        </div>

        <div class="product-card" data-category="e-liquides">
            <img src="img/lemon-time-orange.jpg" alt="Orange Lemon Time">
            <div class="product-info">
                <h3>Orange - LEMON TIME</h3>
                <span class="product-desc">Une limonade pétillante mêlée à l’orange juteuse, relevée par une fraîcheur intense et désaltérante.</span>
                <p class="price">17.500 Ar</p>
                <button class="add-to-cart">AJOUTER AU PANIER</button>
            </div>
        </div>
<div class="product-card" data-category="diy">
    <img src="img/base-pure-50-50-1L.jpg" alt="Base 50/50 1L Pure">
    <div class="product-info">
        <h3>Base 1L 50/50 - PURE</h3>
        <span class="product-desc">Base 100% neutre en 50/50 PG/VG, idéale pour créer vos propres mélanges en grand format.</span>
        <p class="price">150.000 Ar</p>
        <button class="add-to-cart">AJOUTER AU PANIER</button>
    </div>
</div>

<div class="product-card" data-category="diy">
    <img src="img/arome-ragnarok.jpg" alt="Arôme Ragnarok">
    <div class="product-info">
        <h3>Arôme Ragnarok - A&L</h3>
        <span class="product-desc">Un concentré mythique aux fruits rouges givrés : fraises, mûres, framboises et fraîcheur intense.</span>
        <p class="price">45.000 Ar</p>
        <button class="add-to-cart">AJOUTER AU PANIER</button>
    </div>
</div>

<div class="product-card" data-category="diy">
    <img src="img/Additif sweety.jpg" alt="Addif Sweety Swoke">
    <div class="product-info">
        <h3>Additif Sweety - SWOKE</h3>
        <span class="product-desc">L'additif sans sucralose qui préserve vos résistances.</span>
        <p class="price">25.000 Ar</p>
        <button class="add-to-cart">AJOUTER AU PANIER</button>
    </div>
</div>
<div class="product-card" data-category="diy">
    <img src="img/concentré-sakura-dream-30-ml.jpg" alt="Arôme Sakura Dream">
    <div class="product-info">
        <h3>Arôme Sakura Dream - T-Juice</h3>
        <span class="product-desc">Le mariage délicat d'une cerise japonaise printanière et d'une vanille onctueuse.</span>
        <p class="price">45.000 Ar</p>
        <button class="add-to-cart">AJOUTER AU PANIER</button>
    </div>
</div>
<div class="product-card" data-category="materiel">
    <img src="img/kit-aegis-mini-g-geekvape.jpg" alt="Kit Aegis Mini 5">
    <div class="product-info">
        <h3>Kit Aegis Mini 5 - GEEKVAPE</h3>
        <span class="product-desc">Une batterie intégrée de 3200mAh pour une vape longue durée, associée au tank Z Nano 3 pour des saveurs exceptionnelles.</span>
        <p class="price">240.000 Ar</p>
        <button class="add-to-cart">AJOUTER AU PANIER</button>
    </div>
</div>
<div class="product-card" data-category="materiel">
    <img src="img/Kit-Pod-XLIM-PRO- OXVA.jpg" alt="Kit Pod XLIM PRO 3-OXVA">
    <div class="product-info">
        <h3>Kit Pod XLIM PRO 3 - OXVA</h3>
        <span class="product-desc">Une batterie de 1500mAh dans un format compact, avec une puissance réglable jusqu'à 30W et une grip en cuir premium.</span>
        <p class="price">130.000 Ar</p>
        <button class="add-to-cart">AJOUTER AU PANIER</button>
    </div>
</div>
<div class="product-card" data-category="materiel">
    <img src="img/aromamizer-plus-v4-rdta.jpg" alt="Aromamizer Plus V4 RDTA - Steam Crave">
    <div class="product-info">
        <h3>Aromamizer Plus V4 RDTA - STEAM CRAVE</h3>
        <span class="product-desc">Un RDTA de 30mm offrant une polyvalence incroyable, une restitution des saveurs exceptionnelle et une capacité de 11ml ou 13ml selon la configuration.</span>
        <p class="price">210.000 Ar</p>
        <button class="add-to-cart">AJOUTER AU PANIER</button>
    </div>
</div>
<div class="product-card" data-category="materiel">
    <img src="img/razor-aio-luxury--80w-bp-mods.jpg" alt="Razor AIO Luxury Edition - BP Mods">
    <div class="product-info">
        <h3>RAZOR AIO LUXURY EDITION - BP MODS</h3>
        <span class="product-desc">Une édition de luxe offrant jusqu'à 80W de puissance, compatible avec les accus 18650 et dotée d'une finition en résine stabilisée unique.</span>
        <p class="price">320.000 Ar</p>
        <button class="add-to-cart">AJOUTER AU PANIER</button>
    </div>
</div>
    </div> </section>
</div>
</div>
        </div>
</section>
<section class="section-esprit-monkey">
    <div class="mission-white-box">
        <h2 class="title-monkey">L'Esprit <span>BIG MONKEY</span></h2>
        <p class="text-mission">
            Chez Big Monkey, nous ne sommes pas de simples revendeurs. Nous sommes une équipe de passionnés dédiée à l'art de la vape à Madagascar. Notre mission est claire : offrir aux vapoteurs malgaches le meilleur du matériel international et des e-liquides premium.
        </p>
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
            <div class="promo-card">
                <div class="promo-image">
                    <img src="img/Starter Kit Pro 20mg - IVapeGreat.jpg" alt="Starter Kit Pro 20mg - IVapeGreat">
                </div>
                <div class="promo-info">
                    <h4>STARTER KIT PRO 20MG - IVAPEGREAT</h4>
                    <p class="price"><span class="promo-val">100.000 Ar</span></p>
                    <a href="#" class="btn-promo">Découvrir</a>
                </div>
            </div>

            <div class="promo-card">
                <div class="promo-image">
                    <img src="img/MT-10K 2 10ml - Tesla Bar by Teslacigs.jpg" alt="MT-10K 2% 10ml - Tesla Bar by Teslacigs">
                </div>
                <div class="promo-info">
                    <h4>MT-10K 2% 10ML - TESLA BAR BY TESLACIGS</h4>
                    <p class="price"><span class="promo-val">55.000 Ar</span></p>
                    <a href="#" class="btn-promo">Découvrir</a>
                </div>
            </div>

            <div class="promo-card">
                <div class="promo-image">
                    <img src="img/Kit Fighter-X 32K-MINASAWA.jpg" alt="Kit Fighter-X 32K-MINASAWA">
                </div>
                <div class="promo-info">
                    <h4>KIT FIGHTER-X 32K-MINASAWA</h4>
                    <p class="price"><span class="promo-val">60.000</span></p>
                    <a href="#" class="btn-promo">Découvrir</a>
                </div>
            </div>

            <div class="promo-card">
                <div class="promo-image">
                    <img src="img/Starter Kit Pod Flip Pulp 2ml.jpg" alt="Starter Kit Pod Flip Pulp 2ml">
                </div>
                <div class="promo-info">
                    <h4>STARTER KIT POD FLIP PULP 2ML</h4>
                    <p class="price"><span class="promo-val">150.000 Ar</span></p>
                    <a href="#" class="btn-promo">Découvrir</a>
                </div>
            </div>
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
                    <a href="#" class="blog-link">Lire la suite</a>
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
                    <a href="#" class="blog-link">Lire la suite</a>
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
                    <a href="#" class="blog-link">Lire la suite</a>
                </div>
            </div>
        </div>
        <div class="blog-footer">
            <a href="votre-page-blog.html" class="btn-load-more">Voir toute l'actualité</a>
        </div>
    </div>
</section>
</main>
<footer class="main-footer">
    <div class="container">
        <div class="footer-grid">
            
            <div class="footer-col branding">
                <h2 class="footer-logo">BIG <span>MONKEY</span></h2>
                <p class="footer-description">
                    Le spécialiste de la vape à Madagascar. 
                    Retrouvez les meilleures puffs, e-liquides et kits avec une livraison rapide sur Antananarivo et provinces.
                </p>
            </div>

            <div class="footer-col">
                <h4>Navigation</h4>
                <ul>
                    <li><a href="#">Accueil</a></li>
                    <li><a href="#">E-liquides</a></li>
                    <li><a href="#">Puffs & Pods</a></li>
                    <li><a href="#">Le Blog</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Aide & Conseils</h4>
                <ul>
                    <li><a href="#">Guide Nicotine</a></li>
                    <li><a href="#">Entretien matériel</a></li>
                    <li><a href="#">Livraison & Frais</a></li>
                </ul>
            </div>

            <div class="footer-col contact-col">
                <h4>Contact</h4>
                <div class="contact-info">
                    <p><i class="fas fa-map-marker-alt"></i> Antananarivo, Madagascar</p>
                    <p><i class="fas fa-phone"></i> +261 3x xx xx xx</p>
                    <p><i class="fas fa-envelope"></i> contact@pav-mdg.com</p>
                </div>
                
                <div class="social-links">
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
                </div>
            </div>

        </div>

        <div class="footer-bottom">
            <p>&copy; 2026 BIG MONKEY - Tous droits réservés. Interdit aux mineurs (-18).</p>
        </div>
    </div>
</footer>
    <script src="js/script.js"></script>
<script>
    const nomSauvegarde = localStorage.getItem('clientNom');
    if (nomSauvegarde) {
        document.querySelector('.top-bar-right a').innerHTML = `<i class="fas fa-user"></i> ${nomSauvegarde}`;
    }
</script>
</body>
</html>