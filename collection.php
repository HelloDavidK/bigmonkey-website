<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

$rawCategory = isset($_GET['cat']) ? (string) $_GET['cat'] : 'tous';
$category = preg_match('/^[a-zA-Z0-9_-]{1,50}$/', $rawCategory) ? $rawCategory : 'tous';

include 'header.php';

/**
 * @param mixed $value
 */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>

<div class="banner-container">
    <?php if (!isset($_SESSION['user_id'])): ?>
        <a href="compte.php" class="banner-link banner-desktop">
            <img src="img/banière image.jpg" alt="Rejoignez Big Monkey">
        </a>
        <a href="compte.php" class="banner-link banner-mobile">
            <img src="img/baniere-mobile.jpg" alt="Rejoignez Big Monkey Mobile">
        </a>
    <?php else: ?>
        <div class="slider-wrapper" aria-label="Bannières promotionnelles">
            <div class="slides">
                <div class="slide active">
                    <a href="collection.php?cat=nouveautes" class="banner-link banner-desktop">
                        <img src="img/slider1-pc.jpg" alt="Nouveautés E-liquides">
                    </a>
                    <a href="collection.php?cat=nouveautes" class="banner-link banner-mobile">
                        <img src="img/slider1-mobile.jpg" alt="Nouveautés E-liquides Mobile">
                    </a>
                </div>

                <div class="slide">
                    <a href="collection.php?cat=bons-plans" class="banner-link banner-desktop">
                        <img src="img/slider2-pc.jpg" alt="Promotions Matériel">
                    </a>
                    <a href="collection.php?cat=bons-plans" class="banner-link banner-mobile">
                        <img src="img/slider2-mobile.jpg" alt="Promotions Matériel Mobile">
                    </a>
                </div>
            </div>

            <div class="slider-dots" aria-hidden="true">
                <span class="dot active"></span>
                <span class="dot"></span>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="promo-bar-container">
    <div class="promo-bar-content">
        10% DE REMISE SUR VOTRE PREMIÈRE COMMANDE
    </div>
</div>

<section class="demo-assurance-compact">
    <div class="slider-container-wrapper">
        <button class="slider-arrow prev" onclick="moveSlide(-1)" aria-label="Slide précédent">&#10094;</button>

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

        <button class="slider-arrow next" onclick="moveSlide(1)" aria-label="Slide suivant">&#10095;</button>
    </div>
</section>

<div class="mobile-filter-trigger" id="openFilters">
    <i class="fas fa-filter" aria-hidden="true"></i> FILTRER LES PRODUITS
</div>

<div class="container-collection">
    <aside class="sidebar-filters" id="filterDrawer">
        <div class="filter-header-mobile">
            <span>FILTRES</span>
            <span id="closeFilters" style="cursor:pointer; font-size:1.5rem;" aria-label="Fermer">&times;</span>
        </div>

        <h2 class="filter-title">FILTRER PAR</h2>

        <div class="filter-group">
            <h3>MARQUES</h3>
            <ul>
                <li><input type="checkbox" name="brand[]" id="fm" value="Full Moon"> <label for="fm">Full Moon</label></li>
                <li><input type="checkbox" name="brand[]" id="tf" value="Tribal Force"> <label for="tf">Tribal Force</label></li>
                <li><input type="checkbox" name="brand[]" id="just" value="Just."> <label for="just">Just.</label></li>
            </ul>
        </div>

        <div class="filter-group">
            <h3>SAVEURS</h3>
            <ul>
                <li><input type="checkbox" name="saveur[]" id="fruit" value="Fruité"> <label for="fruit">Fruité</label></li>
                <li><input type="checkbox" name="saveur[]" id="frais" value="Frais"> <label for="frais">Frais</label></li>
            </ul>
        </div>

        <button class="btn-apply-filters" type="button">APPLIQUER</button>
    </aside>

    <main class="products-grid-container">
        <div class="collection-header">
            <h1><?= strtoupper(e($category)); ?></h1>
            <p>Découvrez notre sélection exclusive Big Monkey.</p>
        </div>

        <div class="products-grid">
            <?php
            $sql = 'SELECT * FROM produits WHERE is_active = 1';
            $params = [];

            if ($category !== 'tous') {
                $sql .= ' AND (categorie_parent = :cat OR slug = :cat)';
                $params['cat'] = $category;
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($produits)):
                foreach ($produits as $p):
                    $prixRegulier = (float) ($p['prix_regulier'] ?? 0);
                    $prixPromo = isset($p['prix_promo']) && $p['prix_promo'] !== null ? (float) $p['prix_promo'] : null;
                    $prixFinal = $prixPromo ?: $prixRegulier;
                    ?>
                    <div class="product-card">
                        <?php if (!empty($p['is_promo'])): ?>
                            <div class="badge-promo">BONS PLANS</div>
                        <?php endif; ?>

                        <img src="img/produits/<?= e($p['image_principale'] ?? 'placeholder.jpg'); ?>" alt="<?= e($p['nom'] ?? 'Produit'); ?>">

                        <span class="product-brand"><?= e($p['marque'] ?? 'Big Monkey'); ?></span>
                        <h3 class="product-name"><?= e($p['nom'] ?? 'Produit'); ?></h3>

                        <div class="product-price">
                            <?php if ($prixPromo): ?>
                                <span style="text-decoration: line-through; font-size: 0.8rem; color: #888;">
                                    <?= number_format($prixRegulier, 0, '.', ' '); ?> Ar
                                </span><br>
                            <?php endif; ?>

                            <?= number_format($prixFinal, 0, '.', ' '); ?> <span>Ar</span>
                        </div>

                        <a href="produit.php?slug=<?= urlencode((string) ($p['slug'] ?? '')); ?>" class="btn-view">VOIR LE PRODUIT</a>
                    </div>
                <?php
                endforeach;
            else:
                ?>
                <p style="grid-column: 1 / -1; text-align: center; color: #7b8794; font-weight: 700;">
    Aucun produit trouvé dans cette catégorie pour le moment.
</p>
                </p>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const slides = document.querySelectorAll('.slide');
        const dots = document.querySelectorAll('.dot');
        const container = document.querySelector('.slides');
        let currentSlide = 0;

        if (!container || slides.length < 2 || dots.length < 2) {
            return;
        }

        window.setInterval(function () {
            slides[currentSlide].classList.remove('active');
            dots[currentSlide].classList.remove('active');

            currentSlide = (currentSlide + 1) % slides.length;
            container.style.transform = `translateX(-${currentSlide * 100}%)`;

            slides[currentSlide].classList.add('active');
            dots[currentSlide].classList.add('active');
        }, 4000);
    });
</script>

<?php include 'footer.php'; ?>
