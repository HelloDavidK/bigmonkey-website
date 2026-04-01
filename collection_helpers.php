<?php
declare(strict_types=1);

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
 * @param array<int, int|string> $values
 * @param array<string, mixed> $params
 * @return array<int, string>
 */
function buildInPlaceholders(string $prefix, array $values, array &$params): array
{
    $placeholders = [];

    foreach (array_values($values) as $index => $value) {
        $key = ':' . $prefix . '_' . $index;
        $placeholders[] = $key;
        $params[$key] = $value;
    }

    return $placeholders;
}

/**
 * Uniformise quelques valeurs d’attributs pour éviter les doublons visuels
 * comme "fruité" / "Fruité", "classic" / "Classic", etc.
 */
function normalizeAttributeValue(string $attributeName, string $value): string
{
    $value = trim($value);

    if ($value === '') {
        return '';
    }

    if ($attributeName === 'saveur_famille') {
        $normalized = mb_strtolower($value, 'UTF-8');

        $map = [
            'classic' => 'Classic',
            'classique' => 'Classic',
            'fruité' => 'Fruité',
            'fruite' => 'Fruité',
            'gourmand' => 'Gourmand',
            'mentholé' => 'Mentholé',
            'menthole' => 'Mentholé',
            'boisson' => 'Boisson',
        ];

        return $map[$normalized] ?? mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    if ($attributeName === 'sel_nicotine') {
        $normalized = mb_strtolower($value, 'UTF-8');

        if (in_array($normalized, ['oui', 'yes'], true)) {
            return 'Oui';
        }

        if (in_array($normalized, ['non', 'no'], true)) {
            return 'Non';
        }
    }

    return $value;
}

/**
 * @return array<int, int>
 */
function fetchCategoryTreeIds(PDO $pdo, string $categorySlug): array
{
    if ($categorySlug === '' || $categorySlug === 'tous') {
        return [];
    }

    $stmt = $pdo->prepare('SELECT id FROM categories WHERE slug = :slug LIMIT 1');
    $stmt->execute(['slug' => $categorySlug]);
    $rootId = $stmt->fetchColumn();

    if ($rootId === false) {
        return [];
    }

    $allIds = [(int) $rootId];
    $pendingIds = [(int) $rootId];

    while (!empty($pendingIds)) {
        $params = [];
        $placeholders = buildInPlaceholders('parent_category', $pendingIds, $params);

        $sql = 'SELECT id FROM categories WHERE parent_id IN (' . implode(', ', $placeholders) . ')';
        $childrenStmt = $pdo->prepare($sql);
        bindNamedParams($childrenStmt, $params);
        $childrenStmt->execute();

        $childIds = array_map('intval', $childrenStmt->fetchAll(PDO::FETCH_COLUMN));
        $newIds = array_values(array_diff($childIds, $allIds));

        if (empty($newIds)) {
            break;
        }

        $allIds = array_merge($allIds, $newIds);
        $pendingIds = $newIds;
    }

    return array_values(array_unique($allIds));
}

/**
 * @param PDO $pdo
 * @param string $column
 * @param array<int, int> $categoryIds
 * @return array<int, string>
 */
function fetchDistinctStringOptions(PDO $pdo, string $column, array $categoryIds = []): array
{
    $allowedColumns = ['marque'];

    if (!in_array($column, $allowedColumns, true)) {
        return [];
    }

    $sql = "SELECT DISTINCT {$column} AS value
            FROM produits
            WHERE is_active = 1
              AND {$column} IS NOT NULL
              AND {$column} <> ''";

    $params = [];

    if (!empty($categoryIds)) {
        $placeholders = buildInPlaceholders('brand_cat', $categoryIds, $params);
        $sql .= ' AND categorie_id IN (' . implode(', ', $placeholders) . ')';
    }

    $sql .= " ORDER BY {$column} ASC";

    $stmt = $pdo->prepare($sql);
    bindNamedParams($stmt, $params);
    $stmt->execute();

    return array_values(array_filter(array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN))));
}

/**
 * @param PDO $pdo
 * @param string $attributeName
 * @param array<int, int> $categoryIds
 * @return array<int, array{value: string, total: int}>
 */
function fetchAttributeOptions(PDO $pdo, string $attributeName, array $categoryIds = []): array
{
    $sql = 'SELECT pa.attribut_valeur AS value, COUNT(DISTINCT p.id) AS total
            FROM produit_attributs pa
            INNER JOIN produits p ON p.id = pa.produit_id
            WHERE p.is_active = 1
              AND pa.attribut_nom = :attribute
              AND pa.attribut_valeur IS NOT NULL
              AND pa.attribut_valeur <> \'\''; 

    $params = [
        ':attribute' => $attributeName,
    ];

    if (!empty($categoryIds)) {
        $placeholders = buildInPlaceholders('attr_cat', $categoryIds, $params);
        $sql .= ' AND p.categorie_id IN (' . implode(', ', $placeholders) . ')';
    }

    $sql .= ' GROUP BY pa.attribut_valeur
              ORDER BY pa.attribut_valeur ASC';

    $stmt = $pdo->prepare($sql);
    bindNamedParams($stmt, $params);
    $stmt->execute();

    $grouped = [];

    while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
        $rawValue = isset($row['value']) ? (string) $row['value'] : '';
        $value = normalizeAttributeValue($attributeName, $rawValue);
        $total = isset($row['total']) ? (int) $row['total'] : 0;

        if ($value === '' || $total <= 0) {
            continue;
        }

        if (!isset($grouped[$value])) {
            $grouped[$value] = 0;
        }

        $grouped[$value] += $total;
    }

    $options = [];
    foreach ($grouped as $value => $total) {
        $options[] = [
            'value' => $value,
            'total' => $total,
        ];
    }

    usort($options, static function (array $a, array $b) use ($attributeName): int {
        if ($attributeName === 'saveur_famille') {
            $order = [
                'Classic' => 1,
                'Fruité' => 2,
                'Gourmand' => 3,
                'Mentholé' => 4,
                'Boisson' => 5,
            ];

            $rankA = $order[$a['value']] ?? 999;
            $rankB = $order[$b['value']] ?? 999;

            if ($rankA !== $rankB) {
                return $rankA <=> $rankB;
            }
        }

        return strcasecmp($a['value'], $b['value']);
    });

    return $options;
}

function formatCollectionTitle(string $category): string
{
    $map = [
        'eliquides' => 'E-LIQUIDES',
        'diy' => 'DIY',
        'kits' => 'KITS',
        'box' => 'BOX',
        'pod' => 'PODS',
        'puffs' => 'PUFFS',
        'clearos' => 'CLEAROMISEURS',
        'atos' => 'ATOMISEURS',
        'ecigarettes' => 'E-CIGARETTES',
        'nouveautes' => 'NOUVEAUTÉS',
        'bons-plans' => 'BONS PLANS',
        'tous' => 'TOUS LES PRODUITS',
    ];

    if (isset($map[$category])) {
        return $map[$category];
    }

    return mb_strtoupper(str_replace('-', ' ', $category), 'UTF-8');
}

/**
 * @param array<string, mixed> $source
 * @param array<int, string> $attributeFilterKeys
 * @return array<string, array<int, string>>
 */
function normalizeSelectedAttributeFilters(array $source, array $attributeFilterKeys): array
{
    $selectedAttributeFilters = [];

    foreach ($attributeFilterKeys as $attributeKey) {
        $rawValues = $source[$attributeKey] ?? [];

        if ($attributeKey === 'saveur_famille' && empty($rawValues) && isset($source['saveur'])) {
            $rawValues = $source['saveur'];
        }

        $selectedValues = array_values(array_filter((array) $rawValues, static function ($value): bool {
            return is_string($value) && trim($value) !== '';
        }));

        $selectedAttributeFilters[$attributeKey] = array_values(array_unique(array_map(
            static fn(string $value): string => normalizeAttributeValue($attributeKey, $value),
            $selectedValues
        )));
    }

    return $selectedAttributeFilters;
}

/**
 * @return array<string, array<int, string>>
 */
function getAttributeFiltersByCategory(): array
{
    return [
        'eliquides' => [
            'saveur_famille',
            'contenance_ml',
            'ratio_pg_vg',
            'nicotine_mg',
            'sel_nicotine',
        ],
        'diy' => [
            'type_produit',
            'saveur_famille',
            'contenance_ml',
            'ratio_pg_vg',
            'nicotine_mg',
            'sel_nicotine',
        ],
        'kits' => [
            'type_batterie',
            'puissance_w',
            'tirage',
        ],
        'box' => [
            'type_batterie',
            'puissance_w',
        ],
        'pod' => [
            'type_batterie',
            'puissance_w',
            'tirage',
            'contenance_ml',
        ],
        'puffs' => [
            'type_puff',
            'saveur_famille',
            'nb_puffs',
            'nicotine_mg',
            'sel_nicotine',
        ],
        'clearos' => [
            'tirage',
            'contenance_ml',
        ],
        'atos' => [
            'type_ato',
            'tirage',
            'contenance_ml',
        ],
        'ecigarettes' => [
            'type_produit',
            'type_batterie',
            'puissance_w',
            'tirage',
            'contenance_ml',
        ],
        'nouveautes' => [
            'type_produit',
            'saveur_famille',
            'contenance_ml',
            'ratio_pg_vg',
            'nicotine_mg',
            'sel_nicotine',
        ],
        'bons-plans' => [
            'type_produit',
            'saveur_famille',
            'contenance_ml',
            'ratio_pg_vg',
            'nicotine_mg',
            'sel_nicotine',
        ],
        'tous' => [
            'type_produit',
            'saveur_famille',
            'contenance_ml',
            'ratio_pg_vg',
            'nicotine_mg',
            'sel_nicotine',
            'type_batterie',
            'puissance_w',
            'tirage',
            'type_puff',
            'nb_puffs',
            'type_ato',
        ],
    ];
}

/**
 * @return array<string, string>
 */
function getAttributeFilterLabels(): array
{
    return [
        'type_produit' => 'TYPE DE PRODUIT',
        'saveur_famille' => 'SAVEUR',
        'contenance_ml' => 'CONTENANCE (ML)',
        'ratio_pg_vg' => 'RATIO PG/VG',
        'nicotine_mg' => 'NICOTINE (MG)',
        'sel_nicotine' => 'SEL DE NICOTINE',
        'type_batterie' => 'TYPE BATTERIE',
        'puissance_w' => 'PUISSANCE (W)',
        'tirage' => 'TIRAGE',
        'type_puff' => 'TYPE DE PUFF',
        'nb_puffs' => 'NOMBRE DE PUFFS',
        'type_ato' => 'TYPE D’ATO',
    ];
}

/**
 * @param array<string, mixed> $source
 * @return array<string, mixed>
 */
function getCollectionContextFromRequest(PDO $pdo, array $source): array
{
    $rawCategory = isset($source['cat']) ? (string) $source['cat'] : 'tous';
    $category = preg_match('/^[a-zA-Z0-9_-]{1,80}$/', $rawCategory) ? $rawCategory : 'tous';

    $attributeFiltersByCategory = getAttributeFiltersByCategory();
    $attributeFilterLabels = getAttributeFilterLabels();
    $attributeFilterKeys = $attributeFiltersByCategory[$category] ?? $attributeFiltersByCategory['tous'];

    $selectedBrands = array_values(array_filter((array) ($source['brand'] ?? []), static function ($value): bool {
        return is_string($value) && trim($value) !== '';
    }));

    $searchQuery = isset($source['q']) ? trim((string) $source['q']) : '';
    $currentPage = max(1, filter_var($source['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1);
    $productsPerPage = 28;

    $selectedAttributeFilters = normalizeSelectedAttributeFilters($source, $attributeFilterKeys);

    $categoryIds = fetchCategoryTreeIds($pdo, $category);
    $brandOptions = fetchDistinctStringOptions($pdo, 'marque', $categoryIds);

    $attributeFilterOptions = [];
    foreach ($attributeFilterKeys as $attributeKey) {
        $attributeFilterOptions[$attributeKey] = fetchAttributeOptions($pdo, $attributeKey, $categoryIds);
    }

    if (isset($attributeFilterOptions['sel_nicotine'])) {
        $selOptions = $attributeFilterOptions['sel_nicotine'];

        if (
            count($selOptions) === 1 &&
            isset($selOptions[0]['value']) &&
            mb_strtolower(trim((string) $selOptions[0]['value']), 'UTF-8') === 'non'
        ) {
            unset($attributeFilterOptions['sel_nicotine']);
            $attributeFilterKeys = array_values(array_filter(
                $attributeFilterKeys,
                static fn(string $key): bool => $key !== 'sel_nicotine'
            ));
            unset($selectedAttributeFilters['sel_nicotine']);
        }
    }

    $isLockedFlavorCollection = (
        $category === 'eliquides'
        && !empty($selectedAttributeFilters['saveur_famille'])
        && count($selectedAttributeFilters['saveur_famille']) === 1
    );

    $pageTitle = formatCollectionTitle($category);
    $pageIntro = 'Découvrez notre sélection exclusive Big Monkey.';

    if ($category === 'eliquides' && !empty($selectedAttributeFilters['saveur_famille'])) {
        $selectedFlavours = $selectedAttributeFilters['saveur_famille'];

        if (count($selectedFlavours) === 1) {
            $pageTitle = 'E-LIQUIDES ' . mb_strtoupper((string) $selectedFlavours[0], 'UTF-8');
            $pageIntro = 'Découvrez notre sélection d’e-liquides ' . mb_strtolower((string) $selectedFlavours[0], 'UTF-8') . ' chez Big Monkey.';
        } else {
            $pageTitle = 'E-LIQUIDES PAR SAVEUR';
            $pageIntro = 'Affinez votre recherche parmi nos e-liquides selon vos saveurs préférées.';
        }
    }

    return [
        'category' => $category,
        'attributeFilterLabels' => $attributeFilterLabels,
        'attributeFilterKeys' => $attributeFilterKeys,
        'selectedBrands' => $selectedBrands,
        'searchQuery' => $searchQuery,
        'currentPage' => $currentPage,
        'productsPerPage' => $productsPerPage,
        'selectedAttributeFilters' => $selectedAttributeFilters,
        'categoryIds' => $categoryIds,
        'brandOptions' => $brandOptions,
        'attributeFilterOptions' => $attributeFilterOptions,
        'isLockedFlavorCollection' => $isLockedFlavorCollection,
        'pageTitle' => $pageTitle,
        'pageIntro' => $pageIntro,
    ];
}

/**
 * @param array<int, int> $categoryIds
 * @param array<int, string> $selectedBrands
 * @param array<int, string> $attributeFilterKeys
 * @param array<string, array<int, string>> $selectedAttributeFilters
 * @param array<string, mixed> $params
 */
function buildFilteredProductsSql(
    string $category,
    array $categoryIds,
    string $searchQuery,
    array $selectedBrands,
    array $attributeFilterKeys,
    array $selectedAttributeFilters,
    array &$params
): string {
    $sql = 'SELECT p.* FROM produits p WHERE p.is_active = 1';

    if (!empty($categoryIds)) {
        $placeholders = buildInPlaceholders('product_cat', $categoryIds, $params);
        $sql .= ' AND p.categorie_id IN (' . implode(', ', $placeholders) . ')';
    } elseif ($category !== 'tous') {
        $sql .= ' AND 1 = 0';
    }

    if ($searchQuery !== '') {
        $sql .= ' AND (p.nom LIKE :q_nom OR p.marque LIKE :q_marque OR p.slug LIKE :q_slug)';
        $searchLike = '%' . $searchQuery . '%';
        $params[':q_nom'] = $searchLike;
        $params[':q_marque'] = $searchLike;
        $params[':q_slug'] = $searchLike;
    }

    if (!empty($selectedBrands)) {
        $brandPlaceholders = buildInPlaceholders('brand_filter', $selectedBrands, $params);
        $sql .= ' AND p.marque IN (' . implode(', ', $brandPlaceholders) . ')';
    }

    foreach ($attributeFilterKeys as $attributeKey) {
        $selectedValues = $selectedAttributeFilters[$attributeKey] ?? [];
        if (empty($selectedValues)) {
            continue;
        }

        $valuePlaceholders = buildInPlaceholders('attr_' . $attributeKey . '_value', $selectedValues, $params);

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

    return $sql;
}

/**
 * @param array<string, mixed> $context
 * @return array<string, mixed>
 */
function fetchCollectionProductsData(PDO $pdo, array $context): array
{
    $params = [];

    $baseSql = buildFilteredProductsSql(
        $context['category'],
        $context['categoryIds'],
        $context['searchQuery'],
        $context['selectedBrands'],
        $context['attributeFilterKeys'],
        $context['selectedAttributeFilters'],
        $params
    );

    $countSql = 'SELECT COUNT(*) FROM (' . $baseSql . ') AS filtered_products';
    $countStmt = $pdo->prepare($countSql);
    bindNamedParams($countStmt, $params);
    $countStmt->execute();

    $totalProducts = (int) $countStmt->fetchColumn();
    $totalPages = max(1, (int) ceil($totalProducts / $context['productsPerPage']));
    $currentPage = min((int) $context['currentPage'], $totalPages);
    $offset = ($currentPage - 1) * (int) $context['productsPerPage'];

    $sql = $baseSql . ' ORDER BY p.id DESC LIMIT :limit OFFSET :offset';
    $stmt = $pdo->prepare($sql);
    bindNamedParams($stmt, $params);
    $stmt->bindValue(':limit', (int) $context['productsPerPage'], PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'produits' => $produits,
        'totalProducts' => $totalProducts,
        'totalPages' => $totalPages,
        'currentPage' => $currentPage,
    ];
}

/**
 * @param array<string, mixed> $context
 * @param array<string, mixed> $productsData
 * @param array<string, mixed> $querySource
 */
function renderCollectionResultsHtml(array $context, array $productsData, array $querySource): string
{
    $paginationQueryParams = $querySource;
    unset($paginationQueryParams['page']);

    ob_start();
    ?>
    <div class="collection-header">
        <h1><?= e($context['pageTitle']); ?></h1>
        <p><?= e($context['pageIntro']); ?></p>
    </div>

    <?php if ($context['category'] === 'eliquides'): ?>
        <div class="flavor-shortcuts">
            <a href="collection.php?cat=eliquides&saveur_famille[]=Classic" class="<?= in_array('Classic', $context['selectedAttributeFilters']['saveur_famille'] ?? [], true) ? 'active' : ''; ?>">Classic</a>
            <a href="collection.php?cat=eliquides&saveur_famille[]=Fruité" class="<?= in_array('Fruité', $context['selectedAttributeFilters']['saveur_famille'] ?? [], true) ? 'active' : ''; ?>">Fruité</a>
            <a href="collection.php?cat=eliquides&saveur_famille[]=Gourmand" class="<?= in_array('Gourmand', $context['selectedAttributeFilters']['saveur_famille'] ?? [], true) ? 'active' : ''; ?>">Gourmand</a>
            <a href="collection.php?cat=eliquides&saveur_famille[]=Mentholé" class="<?= in_array('Mentholé', $context['selectedAttributeFilters']['saveur_famille'] ?? [], true) ? 'active' : ''; ?>">Mentholé</a>
            <a href="collection.php?cat=eliquides&saveur_famille[]=Boisson" class="<?= in_array('Boisson', $context['selectedAttributeFilters']['saveur_famille'] ?? [], true) ? 'active' : ''; ?>">Boisson</a>
        </div>
    <?php endif; ?>

    <div class="collection-toolbar">
        <div class="collection-count">
            <?= (int) $productsData['totalProducts']; ?> produit<?= (int) $productsData['totalProducts'] > 1 ? 's' : ''; ?>
        </div>
    </div>

    <div class="products-grid">
        <?php if (!empty($productsData['produits'])): ?>
            <?php foreach ($productsData['produits'] as $p): ?>
                <?php
                $prixRegulier = (float) ($p['prix_regulier'] ?? 0);
                $prixPromo = isset($p['prix_promo']) && $p['prix_promo'] !== null ? (float) $p['prix_promo'] : null;
                $prixFinal = $prixPromo ?: $prixRegulier;
                ?>
                <div class="product-card">
                    <a href="produit.php?slug=<?= urlencode((string) ($p['slug'] ?? '')); ?>" class="product-image-wrap">
                        <img src="<?= e(buildProductImagePath(isset($p['image_principale']) ? (string) $p['image_principale'] : null)); ?>" alt="<?= e($p['nom'] ?? 'Produit'); ?>">
                    </a>

                    <div class="product-info">
                        <span class="product-brand"><?= e($p['marque'] ?? 'Big Monkey'); ?></span>
                        <h3 class="product-name"><?= e($p['nom'] ?? 'Produit'); ?></h3>

                        <p class="product-desc">
                            <?= !empty($p['description_courte']) ? nl2br(e($p['description_courte'])) : 'Découvrez ce produit sélectionné par Big Monkey.'; ?>
                        </p>

                        <div class="price"><?= number_format($prixFinal, 0, '.', ' '); ?> Ar</div>
                    </div>

                    <button type="button" class="add-to-cart" aria-label="Ajouter au panier">
                        AJOUTER AU PANIER
                    </button>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="grid-column: 1 / -1; text-align: center; color: #7b8794; font-weight: 700;">
                Aucun produit trouvé dans cette catégorie pour le moment.
            </p>
        <?php endif; ?>
    </div>

    <?php if ((int) $productsData['totalPages'] > 1): ?>
        <nav class="collection-pagination" aria-label="Pagination des produits">
            <?php if ((int) $productsData['currentPage'] > 1): ?>
                <a class="pagination-link" href="<?= e(buildPaginationUrl((int) $productsData['currentPage'] - 1, $paginationQueryParams)); ?>" aria-label="Page précédente">←</a>
            <?php endif; ?>

            <?php for ($page = 1; $page <= (int) $productsData['totalPages']; $page++): ?>
                <a
                    class="pagination-link <?= $page === (int) $productsData['currentPage'] ? 'is-active' : ''; ?>"
                    href="<?= e(buildPaginationUrl($page, $paginationQueryParams)); ?>"
                    <?= $page === (int) $productsData['currentPage'] ? 'aria-current="page"' : ''; ?>
                >
                    <?= $page; ?>
                </a>
            <?php endfor; ?>

            <?php if ((int) $productsData['currentPage'] < (int) $productsData['totalPages']): ?>
                <a class="pagination-link" href="<?= e(buildPaginationUrl((int) $productsData['currentPage'] + 1, $paginationQueryParams)); ?>" aria-label="Page suivante">→</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
    <?php

    return (string) ob_get_clean();
}