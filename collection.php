<?php
// 1. Initialisation et Sécurité
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
require_once 'config.php'; // Toujours en premier pour avoir $pdo !
include 'header.php';

// 2. Récupération de la catégorie via l'URL (ex: collection.php?cat=eliquide)
$category = isset($_GET['cat']) ? $_GET['cat'] : 'tous';
?>
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

<div class="mobile-filter-trigger" id="openFilters">
    <i class="fas fa-filter"></i> FILTRER LES PRODUITS
</div>

<div class="container-collection">
    
    <aside class="sidebar-filters" id="filterDrawer">
        <div class="filter-header-mobile">
            <span>FILTRES</span>
            <span id="closeFilters">&times;</span>
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
        
        <button class="btn-apply-filters">APPLIQUER</button>
    </aside>

    <main class="products-grid-container">
        <div class="collection-header">
            <h1><?php echo strtoupper(htmlspecialchars($category)); ?></h1>
            <p>Découvrez notre sélection exclusive Big Monkey.</p>
        </div>

        <div class="products-grid">
            <?php
            // --- LE MOTEUR DYNAMIQUE ---
            
            // On prépare la requête de base
            $sql = "SELECT * FROM produits WHERE is_active = 1";
            $params = [];

            // Si on ne demande pas "tous" les produits, on filtre par catégorie
            if ($category !== 'tous') {
                $sql .= " AND (categorie_parent = :cat OR slug = :cat)";
                $params['cat'] = $category;
            }

            // Exécution sécurisée
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $produits = $stmt->fetchAll();

            // S'il y a des produits, on les affiche
            if (count($produits) > 0):
                foreach ($produits as $p): 
                    // On vérifie s'il y a un prix promo
                    $prix_final = !empty($p['prix_promo']) ? $p['prix_promo'] : $p['prix_regulier'];
            ?>
                
                <div class="product-card">
                    <?php if ($p['is_promo']): ?>
                        <div class="badge-promo">BONS PLANS</div>
                    <?php endif; ?>
                    
                    <img src="img/produits/<?php echo htmlspecialchars($p['image_principale']); ?>" alt="<?php echo htmlspecialchars($p['nom']); ?>">
                    
                    <span class="product-brand"><?php echo htmlspecialchars($p['marque'] ?? 'Big Monkey'); ?></span>
                    <h3 class="product-name"><?php echo htmlspecialchars($p['nom']); ?></h3>
                    
                    <div class="product-price">
                        <?php if (!empty($p['prix_promo'])): ?>
                            <span style="text-decoration: line-through; font-size: 0.8rem; color: #888;">
                                <?php echo number_format($p['prix_regulier'], 0, '.', ' '); ?> Ar
                            </span><br>
                        <?php endif; ?>
                        
                        <?php echo number_format($prix_final, 0, '.', ' '); ?> <span>Ar</span>
                    </div>
                    
                    <a href="produit.php?slug=<?php echo htmlspecialchars($p['slug']); ?>" class="btn-view">VOIR LE PRODUIT</a>
                </div>

            <?php 
                endforeach; 
            else: 
                // Message si la catégorie est vide
                echo "<p style='grid-column: 1 / -1; text-align: center; color: #fff;'>Aucun produit trouvé dans cette catégorie pour le moment.</p>";
            endif; 
            ?>
        </div>
    </main>
</div>

<?php include 'footer.php'; ?>