<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

$rawCategory = isset($_GET['cat']) ? (string) $_GET['cat'] : 'tous';
$category = preg_match('/^[a-zA-Z0-9_-]{1,50}$/', $rawCategory) ? $rawCategory : 'tous';
$selectedBrands = array_values(array_filter((array) ($_GET['brand'] ?? []), 'is_string'));
$selectedSaveurs = array_values(array_filter((array) ($_GET['saveur'] ?? []), 'is_string'));
$selectedContenances = array_map('intval', (array) ($_GET['contenance'] ?? []));
$selectedRatios = array_values(array_filter((array) ($_GET['ratio_pg_vg'] ?? []), 'is_string'));
$selectedNicotineTypes = array_values(array_filter((array) ($_GET['nicotine_type'] ?? []), 'is_string'));
$selectedNicotineDosages = array_values(array_filter((array) ($_GET['nicotine_dosage'] ?? []), 'is_string'));
$searchQuery = isset($_GET['q']) ? trim((string) $_GET['q']) : '';

include 'header.php';

/**
 * @param mixed $value
 */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * @param PDO $pdo
 * @param string $column
 * @param string|null $category
 * @return array<int, string>
 */
function fetchDistinctStringOptions(PDO $pdo, string $column, ?string $category = null): array
{
    $allowedColumns = ['marque', 'saveur', 'ratio_pg_vg', 'taux_nicotine_dispo'];
    if (!in_array($column, $allowedColumns, true)) {
        return [];
    }

    $sql = "SELECT DISTINCT {$column} AS value FROM produits WHERE is_active = 1 AND {$column} IS NOT NULL AND {$column} <> ''";
    $params = [];

    if ($category !== null && $category !== 'tous') {
        $sql .= ' AND (categorie_parent = :cat_parent OR slug = :cat_slug)';
        $params['cat_parent'] = $category;
        $params['cat_slug'] = $category;
    }

    $sql .= " ORDER BY {$column} ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return array_values(array_filter(array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN))));
}

/**
 * @param PDO $pdo
 * @param string|null $category
 * @return array<int, int>
 */
function fetchDistinctContenances(PDO $pdo, ?string $category = null): array
{
    $sql = 'SELECT DISTINCT contenance FROM produits WHERE is_active = 1 AND contenance IS NOT NULL';
    $params = [];

    if ($category !== null && $category !== 'tous') {
        $sql .= ' AND (categorie_parent = :cat_parent OR slug = :cat_slug)';
        $params['cat_parent'] = $category;
        $params['cat_slug'] = $category;
    }

    $sql .= ' ORDER BY contenance ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return array_values(array_filter(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN))));
}

$brandOptions = fetchDistinctStringOptions($pdo, 'marque', $category);
$saveurOptions = fetchDistinctStringOptions($pdo, 'saveur', $category);
$ratioOptions = fetchDistinctStringOptions($pdo, 'ratio_pg_vg', $category);
$nicotineDosageOptions = fetchDistinctStringOptions($pdo, 'taux_nicotine_dispo', $category);
$contenanceOptions = fetchDistinctContenances($pdo, $category);
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

        <form action="collection.php" method="get">
            <input type="hidden" name="cat" value="<?= e($category); ?>">
            <h2 class="filter-title">FILTRER PAR</h2>

            <div class="filter-group">
                <h3>MARQUES</h3>
                <ul>
                    <?php foreach ($brandOptions as $index => $brand): ?>
                        <?php $id = 'brand_' . $index; ?>
                        <li>
                            <input type="checkbox" name="brand[]" id="<?= e($id); ?>" value="<?= e($brand); ?>" <?= in_array($brand, $selectedBrands, true) ? 'checked' : ''; ?>>
                            <label for="<?= e($id); ?>"><?= e($brand); ?></label>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="filter-group">
                <h3>SAVEURS</h3>
                <ul>
                    <?php foreach ($saveurOptions as $index => $saveur): ?>
                        <?php $id = 'saveur_' . $index; ?>
                        <li>
                            <input type="checkbox" name="saveur[]" id="<?= e($id); ?>" value="<?= e($saveur); ?>" <?= in_array($saveur, $selectedSaveurs, true) ? 'checked' : ''; ?>>
                            <label for="<?= e($id); ?>"><?= e($saveur); ?></label>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="filter-group">
                <h3>CONTENANCES</h3>
                <ul>
                    <?php foreach ($contenanceOptions as $index => $contenance): ?>
                        <?php $id = 'contenance_' . $index; ?>
                        <li>
                            <input type="checkbox" name="contenance[]" id="<?= e($id); ?>" value="<?= e((string) $contenance); ?>" <?= in_array($contenance, $selectedContenances, true) ? 'checked' : ''; ?>>
                            <label for="<?= e($id); ?>"><?= e((string) $contenance); ?> ML</label>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="filter-group">
                <h3>RATIO PG/VG</h3>
                <ul>
                    <?php foreach ($ratioOptions as $index => $ratio): ?>
                        <?php $id = 'ratio_' . $index; ?>
                        <li>
                            <input type="checkbox" name="ratio_pg_vg[]" id="<?= e($id); ?>" value="<?= e($ratio); ?>" <?= in_array($ratio, $selectedRatios, true) ? 'checked' : ''; ?>>
                            <label for="<?= e($id); ?>"><?= e($ratio); ?></label>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="filter-group">
                <h3>TYPE NICOTINE (10ML)</h3>
                <ul>
                    <li>
                        <input type="checkbox" name="nicotine_type[]" id="nic_freebase" value="freebase" <?= in_array('freebase', $selectedNicotineTypes, true) ? 'checked' : ''; ?>>
                        <label for="nic_freebase">Freebase</label>
                    </li>
                    <li>
                        <input type="checkbox" name="nicotine_type[]" id="nic_sel" value="sel" <?= in_array('sel', $selectedNicotineTypes, true) ? 'checked' : ''; ?>>
                        <label for="nic_sel">Sel de nicotine</label>
                    </li>
                </ul>
            </div>

            <div class="filter-group">
                <h3>DOSAGE NICOTINE</h3>
                <ul>
                    <?php foreach ($nicotineDosageOptions as $index => $dosage): ?>
                        <?php $id = 'dosage_' . $index; ?>
                        <li>
                            <input type="checkbox" name="nicotine_dosage[]" id="<?= e($id); ?>" value="<?= e($dosage); ?>" <?= in_array($dosage, $selectedNicotineDosages, true) ? 'checked' : ''; ?>>
                            <label for="<?= e($id); ?>"><?= e($dosage); ?></label>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <?php if ($searchQuery !== ''): ?>
                <input type="hidden" name="q" value="<?= e($searchQuery); ?>">
            <?php endif; ?>
            <button class="btn-apply-filters" type="submit">APPLIQUER</button>
        </form>
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
                $sql .= ' AND (categorie_parent = :cat_parent OR slug = :cat_slug)';
                $params['cat_parent'] = $category;
                $params['cat_slug'] = $category;
            }

            if ($searchQuery !== '') {
                $sql .= ' AND (nom LIKE :q OR marque LIKE :q OR slug LIKE :q)';
                $params['q'] = '%' . $searchQuery . '%';
            }

            if (!empty($selectedBrands)) {
                $brandPlaceholders = [];
                foreach ($selectedBrands as $index => $brand) {
                    $key = ':brand_' . $index;
                    $brandPlaceholders[] = $key;
                    $params[$key] = $brand;
                }
                $sql .= ' AND marque IN (' . implode(', ', $brandPlaceholders) . ')';
            }

            if (!empty($selectedSaveurs)) {
                $saveurPlaceholders = [];
                foreach ($selectedSaveurs as $index => $saveur) {
                    $key = ':saveur_' . $index;
                    $saveurPlaceholders[] = $key;
                    $params[$key] = $saveur;
                }
                $sql .= ' AND saveur IN (' . implode(', ', $saveurPlaceholders) . ')';
            }

            if (!empty($selectedContenances)) {
                $contenancePlaceholders = [];
                foreach ($selectedContenances as $index => $contenance) {
                    $key = ':contenance_' . $index;
                    $contenancePlaceholders[] = $key;
                    $params[$key] = $contenance;
                }
                $sql .= ' AND contenance IN (' . implode(', ', $contenancePlaceholders) . ')';
            }

            if (!empty($selectedRatios)) {
                $ratioPlaceholders = [];
                foreach ($selectedRatios as $index => $ratio) {
                    $key = ':ratio_' . $index;
                    $ratioPlaceholders[] = $key;
                    $params[$key] = $ratio;
                }
                $sql .= ' AND ratio_pg_vg IN (' . implode(', ', $ratioPlaceholders) . ')';
            }

            if (!empty($selectedNicotineTypes)) {
                $hasFreebase = in_array('freebase', $selectedNicotineTypes, true);
                $hasSel = in_array('sel', $selectedNicotineTypes, true);

                if ($hasFreebase && !$hasSel) {
                    $sql .= ' AND (sel_nicotine = 0 OR sel_nicotine IS NULL)';
                } elseif ($hasSel && !$hasFreebase) {
                    $sql .= ' AND sel_nicotine = 1';
                }
            }

            if (!empty($selectedNicotineDosages)) {
                $dosagePlaceholders = [];
                foreach ($selectedNicotineDosages as $index => $dosage) {
                    $key = ':dosage_' . $index;
                    $dosagePlaceholders[] = $key;
                    $params[$key] = $dosage;
                }
                $sql .= ' AND taux_nicotine_dispo IN (' . implode(', ', $dosagePlaceholders) . ')';
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
