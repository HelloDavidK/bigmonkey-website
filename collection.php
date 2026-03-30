<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

$rawCategory = isset($_GET['cat']) ? (string) $_GET['cat'] : 'tous';
$category = preg_match('/^[a-zA-Z0-9_-]{1,50}$/', $rawCategory) ? $rawCategory : 'tous';
$selectedBrands = array_values(array_filter((array) ($_GET['brand'] ?? []), static function ($value): bool {
    return is_string($value) && trim($value) !== '';
}));
$searchQuery = isset($_GET['q']) ? trim((string) $searchQuery = $_GET['q']) : '';
$currentPage = max(1, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1);
$productsPerPage = 28;

$attributeFilterKeys = [
    'saveur_famille',
    'contenance_ml',
    'ratio_pg_vg',
    'nicotine_mg',
    'sel_nicotine',
];

$attributeFilterLabels = [
    'saveur_famille' => 'SAVEUR',
    'contenance_ml' => 'CONTENANCE (ML)',
    'ratio_pg_vg' => 'RATIO PG/VG',
    'nicotine_mg' => 'NICOTINE (MG)',
    'sel_nicotine' => 'SEL DE NICOTINE',
];

$selectedAttributeFilters = [];
foreach ($attributeFilterKeys as $attributeKey) {
    $selectedAttributeFilters[$attributeKey] = array_values(array_filter((array) ($_GET[$attributeKey] ?? []), static function ($value): bool {
        return is_string($value) && trim($value) !== '';
    }));
}

include 'header.php';

/**
 * @param mixed $value
 */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * @param string|null $rawImage
 */
function buildProductImagePath(?string $rawImage): string
{
    $image = trim((string) $rawImage);
    if ($image === '') {
        return 'img/produits/placeholder.jpg';
    }

    if (preg_match('/^(https?:)?\/\//i', $image) === 1) {
        return $image;
    }

    if (strpos($image, 'img/') === 0 || strpos($image, '/img/') === 0) {
        return ltrim($image, '/');
    }

    return 'img/produits/' . ltrim($image, '/');
}

/**
 * @param array<string, mixed> $queryParams
 */
function buildPaginationUrl(int $page, array $queryParams): string
{
    $queryParams['page'] = $page;
    $query = http_build_query($queryParams);

    return 'collection.php' . ($query !== '' ? '?' . $query : '');
}

/**
 * @param PDOStatement $stmt
 * @param array<string, mixed> $params
 */
function bindNamedParams(PDOStatement $stmt, array $params): void
{
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
}

/**
 * @param PDO $pdo
 * @param string $column
 * @param string|null $category
 * @return array<int, string>
 */
function fetchDistinctStringOptions(PDO $pdo, string $column, ?string $category = null): array
{
    $allowedColumns = ['marque'];
    if (!in_array($column, $allowedColumns, true)) {
        return [];
    }

    $sql = "SELECT DISTINCT {$column} AS value FROM produits WHERE is_active = 1 AND {$column} IS NOT NULL AND {$column} <> ''";
    $params = [];

    if ($category !== null && $category !== 'tous') {
        $sql .= ' AND categorie_id = (SELECT id FROM categories WHERE slug = :cat LIMIT 1)';
        $params[':cat'] = $category;
    }

    $sql .= " ORDER BY {$column} ASC";
    $stmt = $pdo->prepare($sql);
    bindNamedParams($stmt, $params);
    $stmt->execute();

    return array_values(array_filter(array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN))));
}

/**
 * @param PDO $pdo
 * @param array<int, string> $attributeKeys
 * @param string|null $category
 * @return array<string, array<int, string>>
 */
function fetchAttributeFilterOptions(PDO $pdo, array $attributeKeys, ?string $category = null): array
{
    if (empty($attributeKeys)) {
        return [];
    }

    $attributePlaceholders = [];
    $params = [];

    foreach ($attributeKeys as $index => $attributeKey) {
        $placeholder = ':attr_name_' . $index;
        $attributePlaceholders[] = $placeholder;
        $params[$placeholder] = $attributeKey;
    }

    $sql = 'SELECT pa.attribut_nom, pa.attribut_valeur
            FROM produit_attributs pa
            INNER JOIN produits p ON p.id = pa.produit_id
            INNER JOIN categories c ON c.id = p.categorie_id
            WHERE p.is_active = 1
              AND pa.attribut_nom IN (' . implode(', ', $attributePlaceholders) . ")
              AND pa.attribut_valeur IS NOT NULL
              AND pa.attribut_valeur <> ''";

    if ($category !== null && $category !== 'tous') {
        $sql .= ' AND c.slug = :cat';
        $params[':cat'] = $category;
    }

    $sql .= ' GROUP BY pa.attribut_nom, pa.attribut_valeur
              ORDER BY pa.attribut_nom ASC, pa.attribut_valeur ASC';

    $stmt = $pdo->prepare($sql);
    bindNamedParams($stmt, $params);
    $stmt->execute();

    $options = [];
    foreach ($attributeKeys as $attributeKey) {
        $options[$attributeKey] = [];
    }

    while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
        $name = isset($row['attribut_nom']) ? (string) $row['attribut_nom'] : '';
        $value = isset($row['attribut_valeur']) ? trim((string) $row['attribut_valeur']) : '';

        if ($name === '' || $value === '' || !array_key_exists($name, $options)) {
            continue;
        }

        $options[$name][] = $value;
    }

    foreach ($options as $name => $values) {
        $options[$name] = array_values(array_unique($values));
    }

    return $options;
}

$brandOptions = fetchDistinctStringOptions($pdo, 'marque', $category);
$attributeFilterOptions = fetchAttributeFilterOptions($pdo, $attributeFilterKeys, $category);
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

            <?php if (!empty($brandOptions)): ?>
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
            <?php endif; ?>

            <?php foreach ($attributeFilterKeys as $attributeKey): ?>
                <?php
                $options = $attributeFilterOptions[$attributeKey] ?? [];
                if (empty($options)) {
                    continue;
                }
                ?>
                <div class="filter-group">
                    <h3><?= e($attributeFilterLabels[$attributeKey] ?? strtoupper(str_replace('_', ' ', $attributeKey))); ?></h3>
                    <ul>
                        <?php foreach ($options as $index => $option): ?>
                            <?php $id = $attributeKey . '_' . $index; ?>
                            <li>
                                <input
                                    type="checkbox"
                                    name="<?= e($attributeKey); ?>[]"
                                    id="<?= e($id); ?>"
                                    value="<?= e($option); ?>"
                                    <?= in_array($option, $selectedAttributeFilters[$attributeKey] ?? [], true) ? 'checked' : ''; ?>
                                >
                                <label for="<?= e($id); ?>"><?= e($option); ?></label>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>

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
            $sql = 'SELECT p.* FROM produits p LEFT JOIN categories c ON c.id = p.categorie_id WHERE p.is_active = 1';
            $params = [];

            if ($category !== 'tous') {
                $sql .= ' AND c.slug = :cat';
                $params[':cat'] = $category;
            }

            if ($searchQuery !== '') {
                $sql .= ' AND (p.nom LIKE :q_nom OR p.marque LIKE :q_marque OR p.slug LIKE :q_slug)';
                $searchLike = '%' . $searchQuery . '%';
                $params[':q_nom'] = $searchLike;
                $params[':q_marque'] = $searchLike;
                $params[':q_slug'] = $searchLike;
            }

            if (!empty($selectedBrands)) {
                $brandPlaceholders = [];
                foreach ($selectedBrands as $index => $brand) {
                    $key = ':brand_' . $index;
                    $brandPlaceholders[] = $key;
                    $params[$key] = $brand;
                }
                $sql .= ' AND p.marque IN (' . implode(', ', $brandPlaceholders) . ')';
            }

            foreach ($attributeFilterKeys as $attributeKey) {
                $selectedValues = $selectedAttributeFilters[$attributeKey] ?? [];
                if (empty($selectedValues)) {
                    continue;
                }

                $valuePlaceholders = [];
                foreach ($selectedValues as $valueIndex => $selectedValue) {
                    $valuePlaceholder = ':attr_' . $attributeKey . '_value_' . $valueIndex;
                    $valuePlaceholders[] = $valuePlaceholder;
                    $params[$valuePlaceholder] = $selectedValue;
                }

                if (!empty($valuePlaceholders)) {
                    $namePlaceholder = ':attr_' . $attributeKey . '_name';
                    $params[$namePlaceholder] = $attributeKey;
                    $sql .= ' AND EXISTS (
                                SELECT 1
                                FROM produit_attributs pa
                                WHERE pa.produit_id = p.id
                                  AND pa.attribut_nom = ' . $namePlaceholder . '
                                  AND pa.attribut_valeur IN (' . implode(', ', $valuePlaceholders) . ')
                              )';
                }
            }

            $countSql = 'SELECT COUNT(*) FROM (' . $sql . ') AS filtered_products';
            $countStmt = $pdo->prepare($countSql);
            bindNamedParams($countStmt, $params);
            $countStmt->execute();
            $totalProducts = (int) $countStmt->fetchColumn();
            $totalPages = max(1, (int) ceil($totalProducts / $productsPerPage));
            $currentPage = min($currentPage, $totalPages);
            $offset = ($currentPage - 1) * $productsPerPage;

            $sql .= ' ORDER BY p.id DESC LIMIT :limit OFFSET :offset';
            $stmt = $pdo->prepare($sql);
            bindNamedParams($stmt, $params);
            $stmt->bindValue(':limit', $productsPerPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>

            <?php if (!empty($produits)): ?>
                <?php foreach ($produits as $p): ?>
                    <?php
                    $prixRegulier = (float) ($p['prix_regulier'] ?? 0);
                    $prixPromo = isset($p['prix_promo']) && $p['prix_promo'] !== null ? (float) $p['prix_promo'] : null;
                    $prixFinal = $prixPromo ?: $prixRegulier;
                    $hasPromo = $prixPromo !== null || !empty($p['is_promo']);
                    ?>
                    <div class="product-card">
                        <?php if ($hasPromo): ?>
                            <div class="badge-promo">PROMO</div>
                        <?php endif; ?>

                        <div class="product-image-wrap">
                            <img src="<?= e(buildProductImagePath(isset($p['image_principale']) ? (string) $p['image_principale'] : null)); ?>" alt="<?= e($p['nom'] ?? 'Produit'); ?>">
                        </div>

                        <div class="product-card-body">
                            <span class="product-brand"><?= e($p['marque'] ?? 'Big Monkey'); ?></span>
                            <h3 class="product-name"><?= e($p['nom'] ?? 'Produit'); ?></h3>

                            <?php if (!empty($p['description_courte'])): ?>
                                <p class="product-short-description"><?= nl2br(e($p['description_courte'])); ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="product-price">
                            <span class="product-price-badge"><?= number_format($prixFinal, 0, '.', ' '); ?> <span>Ar</span></span>
                        </div>

                        <a href="produit.php?slug=<?= urlencode((string) ($p['slug'] ?? '')); ?>" class="btn-view">AJOUTER</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="grid-column: 1 / -1; text-align: center; color: #7b8794; font-weight: 700;">
                    Aucun produit trouvé dans cette catégorie pour le moment.
                </p>
            <?php endif; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <?php
            $paginationQueryParams = $_GET;
            unset($paginationQueryParams['page']);
            ?>
            <nav class="collection-pagination" aria-label="Pagination des produits">
                <?php if ($currentPage > 1): ?>
                    <a class="pagination-link" href="<?= e(buildPaginationUrl($currentPage - 1, $paginationQueryParams)); ?>" aria-label="Page précédente">←</a>
                <?php endif; ?>

                <?php for ($page = 1; $page <= $totalPages; $page++): ?>
                    <a
                        class="pagination-link <?= $page === $currentPage ? 'is-active' : ''; ?>"
                        href="<?= e(buildPaginationUrl($page, $paginationQueryParams)); ?>"
                        <?= $page === $currentPage ? 'aria-current="page"' : ''; ?>
                    >
                        <?= $page; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a class="pagination-link" href="<?= e(buildPaginationUrl($currentPage + 1, $paginationQueryParams)); ?>" aria-label="Page suivante">→</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
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
