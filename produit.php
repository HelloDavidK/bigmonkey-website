<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';
include 'header.php';
include 'add_to_cart_form.php';

$slug = isset($_GET['slug']) ? trim((string) $_GET['slug']) : '';
$slug = preg_match('/^[a-zA-Z0-9_-]{1,160}$/', $slug) ? $slug : '';

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
 * @return array<int, string>
 */
function splitDescriptionParagraphs(?string $text): array
{
    $content = trim((string) $text);

    if ($content === '') {
        return [];
    }

    $parts = preg_split('/\R{2,}/', $content) ?: [];
    $parts = array_values(array_filter(array_map(static function ($item): string {
        return trim((string) $item);
    }, $parts)));

    if (!empty($parts)) {
        return $parts;
    }

    return [$content];
}

/**
 * @param array<string, array<int, string>> $attributes
 */
function getFirstAttributeValue(array $attributes, string $key): string
{
    if (!isset($attributes[$key]) || empty($attributes[$key][0])) {
        return '';
    }

    return trim((string) $attributes[$key][0]);
}

/**
 * @param array<string, array<int, string>> $attributes
 */
function getJoinedAttributeValue(array $attributes, string $key, string $separator = ', '): string
{
    if (!isset($attributes[$key])) {
        return '';
    }

    $values = array_values(array_filter(array_map(static function ($value): string {
        return trim((string) $value);
    }, $attributes[$key])));

    return implode($separator, array_unique($values));
}

function normalizeText(string $value): string
{
    $value = trim(mb_strtolower($value, 'UTF-8'));

    $replace = [
        'à' => 'a', 'â' => 'a', 'ä' => 'a',
        'ç' => 'c',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'î' => 'i', 'ï' => 'i',
        'ô' => 'o', 'ö' => 'o',
        'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ÿ' => 'y',
    ];

    return strtr($value, $replace);
}

function isZeroNicotineValue(string $value): bool
{
    $normalized = normalizeText($value);
    $normalized = str_replace([' ', ','], ['', '.'], $normalized);

    return in_array($normalized, ['0', '0mg', '0mg/ml', '0ml', 'sansnicotine', '0mgnicotine'], true)
        || preg_match('/^0+(\.0+)?mg$/', $normalized) === 1
        || preg_match('/^0+(\.0+)?$/', $normalized) === 1;
}

function formatAr(float $price): string
{
    return number_format($price, 0, '.', ' ') . ' Ar';
}

function extractMlValue(string $value): int
{
    if (preg_match('/(\d+)/', $value, $matches) === 1) {
        return (int) $matches[1];
    }

    return 0;
}

function descriptionLooksLikeHtml(string $value): bool
{
    return preg_match('/<[^>]+>/', $value) === 1;
}

function sanitizeProductDescriptionHtml(?string $html): string
{
    $content = trim((string) $html);

    if ($content === '') {
        return '';
    }

    $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $content = strip_tags($content, '<h2><h3><h4><h5><p><br><strong><b><em><i><ul><ol><li><a>');

    $content = preg_replace_callback(
        '/<a\s+[^>]*href=(["\'])(.*?)\1[^>]*>/i',
        static function ($matches): string {
            $url = trim((string) ($matches[2] ?? ''));

            if ($url !== '' && preg_match('/^(javascript:|data:)/i', $url) === 1) {
                return '<a>';
            }

            if (stripos($matches[0], 'rel=') !== false) {
                return $matches[0];
            }

            return str_replace('<a', '<a rel="nofollow noopener"', $matches[0]);
        },
        $content
    ) ?? $content;

    return $content;
}

function formatContenanceDisplay(string $value): string
{
    $trimmed = trim($value);

    if ($trimmed === '') {
        return '';
    }

    if (preg_match('/ml$/i', $trimmed) === 1) {
        return $trimmed;
    }

    return $trimmed . ' ml';
}

/**
 * @param array<int, int|string> $ids
 */
function sanitizeIdList(array $ids): string
{
    $clean = array_values(array_unique(array_map('intval', $ids)));
    $clean = array_filter($clean, static fn(int $id): bool => $id > 0);

    if (empty($clean)) {
        return '0';
    }

    return implode(',', $clean);
}

$product = null;
$attributes = [];
$boosters = [];
$relatedSameLine = [];
$relatedSimilar = [];
$relatedSameLineTitle = 'Dans la même gamme';
$relatedSimilarTitle = 'Vous pourriez aussi aimer';

if ($slug !== '') {
    $stmt = $pdo->prepare('
        SELECT p.*, c.slug AS categorie_slug, c.nom AS categorie_nom
        FROM produits p
        LEFT JOIN categories c ON c.id = p.categorie_id
        WHERE p.slug = :slug AND p.is_active = 1
        LIMIT 1
    ');
    $stmt->execute(['slug' => $slug]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    if ($product) {
        $attrStmt = $pdo->prepare('
            SELECT attribut_nom, attribut_valeur
            FROM produit_attributs
            WHERE produit_id = :produit_id
            ORDER BY id ASC
        ');
        $attrStmt->execute(['produit_id' => $product['id']]);
        $rows = $attrStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $name = trim((string) ($row['attribut_nom'] ?? ''));
            $value = trim((string) ($row['attribut_valeur'] ?? ''));

            if ($name === '' || $value === '') {
                continue;
            }

            if (!isset($attributes[$name])) {
                $attributes[$name] = [];
            }

            $attributes[$name][] = $value;
        }

        $nicotine = getFirstAttributeValue($attributes, 'nicotine_mg');
        $isZeroNicotine = $nicotine !== '' && isZeroNicotineValue($nicotine);

        if ($isZeroNicotine) {
            $boostersStmt = $pdo->prepare("
                SELECT DISTINCT p.*, c.nom AS categorie_nom, c.slug AS categorie_slug
                FROM produits p
                LEFT JOIN categories c ON c.id = p.categorie_id
                LEFT JOIN produit_attributs pa ON pa.produit_id = p.id
                WHERE p.is_active = 1
                  AND p.id <> :current_product_id
                  AND (
                        (
                            LOWER(COALESCE(pa.attribut_nom, '')) = 'type_produit'
                            AND (
                                LOWER(COALESCE(pa.attribut_valeur, '')) LIKE '%booster%'
                                OR LOWER(COALESCE(pa.attribut_valeur, '')) LIKE '%boost%'
                            )
                        )
                        OR LOWER(COALESCE(p.nom, '')) LIKE '%booster%'
                        OR LOWER(COALESCE(p.slug, '')) LIKE '%booster%'
                        OR LOWER(COALESCE(c.nom, '')) LIKE '%booster%'
                        OR LOWER(COALESCE(c.slug, '')) LIKE '%booster%'
                  )
                ORDER BY
                    CASE
                        WHEN LOWER(COALESCE(p.nom, '')) LIKE '%booster%' THEN 0
                        ELSE 1
                    END,
                    p.id DESC
                LIMIT 4
            ");
            $boostersStmt->execute([
                'current_product_id' => (int) $product['id'],
            ]);
            $boosters = $boostersStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        $currentProductId = (int) $product['id'];
        $currentCategoryId = (int) ($product['categorie_id'] ?? 0);
        $currentGamme = trim((string) ($product['gamme'] ?? ''));
        $currentMarque = trim((string) ($product['marque'] ?? ''));
        $currentTypeProduit = getFirstAttributeValue($attributes, 'type_produit');
        $currentContenance = getFirstAttributeValue($attributes, 'contenance_ml');
        $currentSaveurFamille = getJoinedAttributeValue($attributes, 'saveur_famille');
        $currentSaveur = getJoinedAttributeValue($attributes, 'saveur');

        // 1) Produits de la même gamme
        if ($currentGamme !== '') {
            $sameLineStmt = $pdo->prepare('
                SELECT p.*, c.nom AS categorie_nom, c.slug AS categorie_slug
                FROM produits p
                LEFT JOIN categories c ON c.id = p.categorie_id
                WHERE p.is_active = 1
                  AND p.id <> :current_id
                  AND p.gamme = :gamme_value
                ORDER BY
                  CASE WHEN p.marque = :order_marque THEN 0 ELSE 1 END,
                  p.id DESC
                LIMIT 4
            ');
            $sameLineStmt->execute([
                'current_id' => $currentProductId,
                'gamme_value' => $currentGamme,
                'order_marque' => $currentMarque,
            ]);
            $relatedSameLine = $sameLineStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $relatedSameLineTitle = 'Dans la même gamme';
        }

        // Fallback : autres produits de la même marque
        if (empty($relatedSameLine) && $currentMarque !== '') {
            $sameBrandOrderHasGamme = $currentGamme !== '' ? 1 : 0;

            $sameBrandStmt = $pdo->prepare('
                SELECT p.*, c.nom AS categorie_nom, c.slug AS categorie_slug
                FROM produits p
                LEFT JOIN categories c ON c.id = p.categorie_id
                WHERE p.is_active = 1
                  AND p.id <> :current_id
                  AND p.marque = :brand_value
                ORDER BY
                  CASE
                    WHEN p.gamme = :order_gamme_value AND :order_has_gamme = 1 THEN 0
                    ELSE 1
                  END,
                  p.id DESC
                LIMIT 4
            ');
            $sameBrandStmt->execute([
                'current_id' => $currentProductId,
                'brand_value' => $currentMarque,
                'order_gamme_value' => $currentGamme,
                'order_has_gamme' => $sameBrandOrderHasGamme,
            ]);
            $relatedSameLine = $sameBrandStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $relatedSameLineTitle = 'Autres produits de la marque';
        }

        // 2) Produits similaires
        $excludeIds = [$currentProductId];
        foreach ($relatedSameLine as $item) {
            $excludeIds[] = (int) ($item['id'] ?? 0);
        }

        $similarConditions = [];
        $similarParams = [];

        if ($currentCategoryId > 0) {
            $similarConditions[] = 'p.categorie_id = :sim_category_id';
            $similarParams['sim_category_id'] = $currentCategoryId;
        }

        if ($currentMarque !== '') {
            $similarConditions[] = 'p.marque = :sim_marque';
            $similarParams['sim_marque'] = $currentMarque;
        }

        if ($currentGamme !== '') {
            $similarConditions[] = 'p.gamme = :sim_gamme';
            $similarParams['sim_gamme'] = $currentGamme;
        }

        $attrJoin = '
            LEFT JOIN produit_attributs pa_type ON pa_type.produit_id = p.id AND pa_type.attribut_nom = "type_produit"
            LEFT JOIN produit_attributs pa_cont ON pa_cont.produit_id = p.id AND pa_cont.attribut_nom = "contenance_ml"
            LEFT JOIN produit_attributs pa_sf ON pa_sf.produit_id = p.id AND pa_sf.attribut_nom = "saveur_famille"
            LEFT JOIN produit_attributs pa_sv ON pa_sv.produit_id = p.id AND pa_sv.attribut_nom = "saveur"
        ';

        if ($currentTypeProduit !== '') {
            $similarConditions[] = 'pa_type.attribut_valeur = :sim_type_produit';
            $similarParams['sim_type_produit'] = $currentTypeProduit;
        }

        if ($currentContenance !== '') {
            $similarConditions[] = 'pa_cont.attribut_valeur = :sim_contenance';
            $similarParams['sim_contenance'] = $currentContenance;
        }

        if ($currentSaveurFamille !== '') {
            $saveurFamilleParts = array_values(array_filter(array_map('trim', explode(',', $currentSaveurFamille))));
            foreach ($saveurFamilleParts as $index => $part) {
                $key = 'sim_sf_' . $index;
                $similarConditions[] = "pa_sf.attribut_valeur LIKE :$key";
                $similarParams[$key] = '%' . $part . '%';
            }
        }

        if ($currentSaveur !== '') {
            $saveurParts = array_values(array_filter(array_map('trim', explode(',', $currentSaveur))));
            foreach ($saveurParts as $index => $part) {
                $key = 'sim_sv_' . $index;
                $similarConditions[] = "pa_sv.attribut_valeur LIKE :$key";
                $similarParams[$key] = '%' . $part . '%';
            }
        }

        if (!empty($similarConditions)) {
            $excludeSql = sanitizeIdList($excludeIds);

            $similarOrderCategoryId = $currentCategoryId;
            $similarHasCategory = $currentCategoryId > 0 ? 1 : 0;

            $similarSql = '
                SELECT DISTINCT p.*, c.nom AS categorie_nom, c.slug AS categorie_slug
                FROM produits p
                LEFT JOIN categories c ON c.id = p.categorie_id
                ' . $attrJoin . '
                WHERE p.is_active = 1
                  AND p.id NOT IN (' . $excludeSql . ')
                  AND (' . implode(' OR ', $similarConditions) . ')
                ORDER BY
                  CASE
                    WHEN p.categorie_id = :order_category_id AND :order_has_category = 1 THEN 0
                    ELSE 1
                  END,
                  p.id DESC
                LIMIT 4
            ';

            $similarParams['order_category_id'] = $similarOrderCategoryId;
            $similarParams['order_has_category'] = $similarHasCategory;

            $similarStmt = $pdo->prepare($similarSql);
            $similarStmt->execute($similarParams);
            $relatedSimilar = $similarStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        // Fallback si trop peu de produits similaires
        if (count($relatedSimilar) < 4 && $currentCategoryId > 0) {
            $excludeIdsFallback = $excludeIds;
            foreach ($relatedSimilar as $item) {
                $excludeIdsFallback[] = (int) ($item['id'] ?? 0);
            }

            $remainingLimit = max(0, 4 - count($relatedSimilar));

            if ($remainingLimit > 0) {
                $fallbackSql = '
                    SELECT p.*, c.nom AS categorie_nom, c.slug AS categorie_slug
                    FROM produits p
                    LEFT JOIN categories c ON c.id = p.categorie_id
                    WHERE p.is_active = 1
                      AND p.id NOT IN (' . sanitizeIdList($excludeIdsFallback) . ')
                      AND p.categorie_id = :fallback_category_id
                    ORDER BY p.id DESC
                    LIMIT ' . $remainingLimit;

                $fallbackStmt = $pdo->prepare($fallbackSql);
                $fallbackStmt->execute([
                    'fallback_category_id' => $currentCategoryId,
                ]);
                $fallbackProducts = $fallbackStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

                if (!empty($fallbackProducts)) {
                    $relatedSimilar = array_merge($relatedSimilar, $fallbackProducts);
                }
            }
        }
    }
}
?>
<style>
:root {
    --product-bg: #ffffff;
    --product-border: #e5e7eb;
    --product-border-strong: #d1d5db;
    --product-text: #111827;
    --product-text-soft: #4b5563;
    --product-text-muted: #6b7280;
    --product-accent: #f0e22a;
    --product-danger: #ff2a1f;
    --product-shadow-soft: 0 10px 30px rgba(17, 24, 39, 0.05);
    --product-radius-lg: 22px;
    --product-radius-md: 18px;
    --product-radius-sm: 14px;
}

.product-page-wrap {
    max-width: 1380px;
    margin: 24px auto 40px;
    padding: 0 18px;
}

.product-not-found {
    background: var(--product-bg);
    border: 1px solid var(--product-border);
    border-radius: var(--product-radius-lg);
    padding: 32px;
    text-align: center;
    box-shadow: var(--product-shadow-soft);
}

.product-layout {
    display: grid;
    grid-template-columns: minmax(520px, 620px) minmax(420px, 1fr);
    gap: 24px 32px;
    align-items: start;
}

.product-left-column,
.product-right-column {
    min-width: 0;
}

.product-right-column {
    position: sticky;
    top: 18px;
    align-self: start;
}

.product-gallery-panel,
.product-content-panel,
.product-description-panel,
.product-recommendation-panel {
    background: var(--product-bg);
    border: 1px solid var(--product-border);
    border-radius: var(--product-radius-lg);
    box-shadow: var(--product-shadow-soft);
}

.product-gallery-panel {
    padding: 18px;
    display: flex;
}

.product-gallery-grid {
    display: grid;
    grid-template-columns: 64px minmax(0, 1fr);
    gap: 18px;
    align-items: stretch;
    width: 100%;
    min-height: 100%;
}

.product-thumbs {
    display: flex;
    flex-direction: column;
    gap: 12px;
    align-self: start;
}

.product-thumb {
    width: 56px;
    height: 56px;
    border: 1px solid var(--product-border-strong);
    border-radius: 12px;
    background: #fff;
    padding: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
}

.product-thumb:hover {
    transform: translateY(-1px);
}

.product-thumb.is-active {
    border-color: #111;
    box-shadow: 0 0 0 2px rgba(17, 24, 39, 0.06);
}

.product-thumb img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    display: block;
}

.product-main-visual {
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    background: #fff;
    min-height: 500px;
    padding: 20px 24px;
    border-radius: 18px;
}

.product-main-visual img {
    display: block;
    width: 100%;
    max-width: 430px;
    max-height: 430px;
    height: auto;
    object-fit: contain;
}

.product-content-panel {
    padding: 26px 30px 22px;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
}

.product-title {
    margin: 0;
    font-size: 38px;
    line-height: 1.08;
    font-weight: 900;
    color: #000;
    text-transform: uppercase;
    letter-spacing: -0.02em;
}

.product-title-accent {
    width: 84px;
    height: 8px;
    margin: 14px 0 18px;
    border-radius: 999px;
    background: var(--product-accent);
    transform: skewX(-22deg);
}

.product-price-inline {
    display: flex;
    align-items: baseline;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 16px;
}

.product-price-old {
    font-size: 19px;
    font-weight: 900;
    color: #111;
    text-decoration: line-through;
    line-height: 1;
}

.product-price-current {
    font-size: 22px;
    font-weight: 900;
    color: var(--product-danger);
    line-height: 1;
}

.product-price-current.no-promo {
    color: #111;
}

.product-short-description {
    margin: 0;
    padding: 14px 0;
    border-top: 1px solid var(--product-border);
    border-bottom: 1px solid var(--product-border);
    font-size: 15px;
    line-height: 1.7;
    color: var(--product-text-soft);
}

.product-tech {
    margin-top: 18px;
    padding: 16px 18px;
    background: #f9fafb;
    border: 1px solid var(--product-border);
    border-radius: 16px;
}

.product-tech-title {
    margin: 0 0 10px;
    font-size: 16px;
    font-weight: 900;
    color: #111;
}

.product-tech-list {
    margin: 0;
    padding: 0;
    list-style: none;
    display: grid;
    gap: 8px;
}

.product-tech-list li {
    margin: 0;
    font-size: 14px;
    line-height: 1.55;
    color: var(--product-text-soft);
}

.product-tech-list strong {
    color: #111;
    font-weight: 800;
}

.product-purchase-bar {
    margin-top: 18px;
    background: transparent;
}

.purchase-bundle-form {
    width: 100%;
}

.purchase-card,
.purchase-boosters-panel,
.purchase-reassurance-card {
    background: #fff;
    border: 1px solid #111;
    border-radius: var(--product-radius-lg);
    box-shadow: var(--product-shadow-soft);
}

.purchase-card {
    padding: 14px 18px;
}

.purchase-main-inline {
    display: grid;
    grid-template-columns: 60px minmax(0, 1fr) auto auto;
    gap: 14px;
    align-items: center;
}

.purchase-product-thumb {
    width: 58px;
    height: 58px;
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
}

.purchase-product-thumb img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    display: block;
}

.purchase-product-meta {
    min-width: 0;
}

.purchase-product-title {
    margin: 0;
    font-size: 16px;
    line-height: 1.15;
    font-weight: 900;
    color: #111;
    text-transform: uppercase;
}

.purchase-product-brand {
    margin-top: 3px;
    font-size: 13px;
    font-weight: 700;
    color: var(--product-text-muted);
}

.purchase-product-note {
    margin-top: 4px;
    font-size: 12px;
    color: #b45309;
    font-weight: 900;
}

.purchase-price-stack {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 2px;
    min-width: 112px;
}

.purchase-price-line {
    display: flex;
    align-items: baseline;
    flex-wrap: wrap;
    gap: 8px;
}

.purchase-old-price {
    font-size: 15px;
    font-weight: 900;
    color: #111;
    text-decoration: line-through;
    line-height: 1;
}

.purchase-final-price {
    font-size: 17px;
    font-weight: 900;
    color: #111;
    line-height: 1;
    white-space: nowrap;
}

.purchase-final-price.is-promo {
    color: var(--product-danger);
}

.purchase-qty-wrap {
    width: 82px;
    flex-shrink: 0;
}

.purchase-qty-controls {
    height: 38px;
    border: 2px solid #111;
    border-radius: 999px;
    display: grid;
    grid-template-columns: 24px 1fr 24px;
    align-items: center;
    overflow: hidden;
    background: #fff;
    padding: 0 4px;
}

.qty-btn {
    width: 100%;
    height: 100%;
    border: none;
    background: transparent;
    color: var(--product-danger);
    font-size: 22px;
    line-height: 1;
    cursor: pointer;
    font-weight: 900;
    display: flex;
    align-items: center;
    justify-content: center;
}

.qty-btn:hover {
    background: rgba(17, 24, 39, 0.03);
}

.qty-input {
    width: 100%;
    height: 100%;
    border: none;
    background: transparent;
    text-align: center;
    font-size: 15px;
    font-weight: 900;
    color: #111;
    outline: none;
    -moz-appearance: textfield;
}

.qty-input::-webkit-outer-spin-button,
.qty-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.purchase-boosters-wrap {
    margin-top: 12px;
    text-align: center;
}

.purchase-boosters-toggle {
    width: auto;
    border: none;
    background: transparent;
    padding: 0;
    cursor: pointer;
    font: inherit;
}

.purchase-boosters-toggle-inner {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    min-height: 46px;
    min-width: 420px;
    background: #111;
    color: #fff;
    padding: 0 24px;
    border-radius: 999px;
    font-size: 20px;
    font-weight: 900;
    line-height: 1;
    box-shadow: var(--product-shadow-soft);
}

.purchase-boosters-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    transition: transform 0.2s ease;
}

.purchase-boosters-panel {
    margin-top: 10px;
    padding: 10px 12px;
    display: none;
    text-align: left;
}

.purchase-boosters.is-open .purchase-boosters-panel {
    display: block;
}

.purchase-boosters.is-open .purchase-boosters-icon {
    transform: rotate(180deg);
}

.purchase-booster-row {
    display: grid;
    grid-template-columns: 44px minmax(0, 1fr) auto auto;
    gap: 12px;
    align-items: center;
    padding: 12px 8px;
    border-radius: 16px;
}

.purchase-booster-row + .purchase-booster-row {
    margin-top: 4px;
}

.purchase-booster-thumb {
    width: 42px;
    height: 42px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.purchase-booster-thumb img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    display: block;
}

.purchase-booster-meta {
    min-width: 0;
}

.purchase-booster-title {
    margin: 0;
    font-size: 14px;
    font-weight: 900;
    line-height: 1.2;
    color: #111;
    text-transform: uppercase;
}

.purchase-booster-brand {
    margin-top: 2px;
    font-size: 12px;
    font-weight: 700;
    color: var(--product-text-muted);
}

.purchase-booster-price-wrap {
    min-width: 92px;
}

.purchase-booster-price-line {
    display: flex;
    align-items: baseline;
    flex-wrap: wrap;
    gap: 6px;
}

.purchase-booster-old-price {
    font-size: 13px;
    font-weight: 900;
    color: #111;
    text-decoration: line-through;
    line-height: 1;
}

.purchase-booster-price {
    font-size: 15px;
    font-weight: 900;
    color: #111;
    white-space: nowrap;
    line-height: 1;
}

.purchase-booster-price.is-promo {
    color: var(--product-danger);
}

.purchase-booster-qty .purchase-qty-controls {
    width: 82px;
    height: 38px;
    border-width: 2px;
    grid-template-columns: 24px 1fr 24px;
    padding: 0 4px;
}

.purchase-footer {
    margin-top: 12px;
}

.purchase-add-cart {
    width: 100%;
    min-height: 60px;
    border: none;
    border-radius: 16px;
    background: var(--product-accent);
    color: #111;
    font-size: 21px;
    font-weight: 900;
    cursor: pointer;
    text-transform: uppercase;
    box-shadow: var(--product-shadow-soft);
    transition: transform 0.2s ease, background 0.2s ease;
}

.purchase-add-cart:hover {
    background: #e6d91f;
    transform: translateY(-1px);
}

.purchase-reassurance-card {
    margin-top: 12px;
    padding: 16px 18px;
}

.purchase-reassurance-list {
    display: grid;
    grid-template-columns: 1fr;
    gap: 14px;
}

.purchase-reassurance-item {
    display: grid;
    grid-template-columns: 52px minmax(0, 1fr);
    gap: 12px;
    align-items: center;
}

.purchase-reassurance-icon {
    width: 52px;
    height: 52px;
    border-radius: 12px;
    background: #f9fafb;
    border: 1px solid var(--product-border);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.purchase-reassurance-icon img {
    width: 34px;
    height: 34px;
    object-fit: contain;
    display: block;
}

.purchase-reassurance-text h3 {
    margin: 0 0 4px;
    font-size: 14px;
    line-height: 1.2;
    font-weight: 900;
    color: #111;
    text-transform: uppercase;
}

.purchase-reassurance-text p {
    margin: 0;
    font-size: 13px;
    line-height: 1.55;
    color: var(--product-text-soft);
}

.product-description-panel {
    margin-top: 24px;
    padding: 28px 30px;
}

.product-description-content,
.nicotine-help-block {
    font-family: inherit;
    color: var(--product-text-soft);
}

.product-description-content h2,
.product-description-content h3,
.product-description-content h4,
.product-description-content h5,
.nicotine-help-block h2,
.nicotine-help-block h3,
.nicotine-help-block h4,
.nicotine-help-block h5,
.product-recommendation-title {
    margin: 0 0 12px;
    color: #111;
    font-weight: 800;
    letter-spacing: -0.01em;
    font-family: inherit;
}

.product-description-content h2,
.nicotine-help-block h2,
.product-recommendation-title {
    font-size: 28px;
    line-height: 1.25;
}

.product-description-content h3,
.nicotine-help-block h3 {
    font-size: 22px;
    line-height: 1.3;
}

.product-description-content h4,
.product-description-content h5,
.nicotine-help-block h4,
.nicotine-help-block h5 {
    font-size: 18px;
    line-height: 1.35;
}

.product-description-content p,
.product-description-content li,
.nicotine-help-block p,
.nicotine-help-block li {
    font-size: 15px;
    line-height: 1.72;
    color: var(--product-text-soft);
    font-family: inherit;
}

.product-description-content p,
.nicotine-help-block p {
    margin: 0 0 16px;
}

.product-description-content ul,
.product-description-content ol,
.nicotine-help-block ul,
.nicotine-help-block ol {
    margin: 0 0 18px 22px;
    padding: 0;
}

.product-description-content li + li,
.nicotine-help-block li + li {
    margin-top: 6px;
}

.product-description-content strong,
.product-description-content b,
.nicotine-help-block strong,
.nicotine-help-block b {
    color: #111;
    font-weight: 800;
}

.product-description-content a,
.nicotine-help-block a {
    color: #111;
    font-weight: 700;
    text-decoration: underline;
}

.nicotine-help-block {
    margin-top: 26px;
    padding: 22px 22px 0;
    border-top: 1px solid var(--product-border);
    background: #f9fafb;
    border-radius: 18px;
}

.nicotine-help-image {
    margin: 14px 0 18px;
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid var(--product-border);
    background: #fff;
    max-width: 760px;
}

.nicotine-help-image img {
    width: 100%;
    height: auto;
    display: block;
}

/* =========================
   RECOMMANDATIONS
   ========================= */

.product-recommendation-panel {
    margin-top: 24px;
    padding: 24px 24px 22px;
    overflow: hidden;
}

.product-recommendation-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 18px;
}

.product-recommendation-subtitle {
    margin: 0;
    font-size: 14px;
    line-height: 1.5;
    color: var(--product-text-muted);
}

.product-recommendation-grid {
    display: flex;
    flex-wrap: nowrap;
    gap: 24px;
    overflow-x: auto;
    overflow-y: hidden;
    scroll-snap-type: x mandatory;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    padding-bottom: 6px;
}

.product-recommendation-grid::-webkit-scrollbar {
    display: none;
}

.product-mini-card {
    flex: 0 0 calc((100% - 72px) / 4);
    min-width: calc((100% - 72px) / 4);
    max-width: calc((100% - 72px) / 4);
    scroll-snap-align: start;
    border: 1px solid var(--product-border);
    border-radius: 18px;
    background: #fff;
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.product-mini-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--product-shadow-soft);
    border-color: var(--product-border-strong);
}

.product-mini-link {
    display: block;
    color: inherit;
    text-decoration: none;
    height: 100%;
}

.product-mini-image-wrap {
    height: 210px;
    padding: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    border-bottom: 1px solid var(--product-border);
}

.product-mini-image-wrap img {
    max-width: 100%;
    max-height: 100%;
    width: auto;
    height: auto;
    object-fit: contain;
    display: block;
}

.product-mini-body {
    padding: 16px 16px 18px;
    text-align: center;
}

.product-mini-brand {
    margin: 0 0 6px;
    font-size: 14px;
    font-weight: 700;
    color: var(--product-text-muted);
    line-height: 1.2;
    text-align: center;
}

.product-mini-title {
    margin: 0 0 10px;
    font-size: 16px;
    font-weight: 900;
    line-height: 1.2;
    color: #111;
    text-align: center;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 38px;
}

.product-mini-price-line {
    display: flex;
    align-items: baseline;
    justify-content: center;
    flex-wrap: wrap;
    gap: 8px;
}

.product-mini-old-price {
    font-size: 13px;
    font-weight: 800;
    color: #111;
    text-decoration: line-through;
    line-height: 1;
}

.product-mini-price {
    font-size: 16px;
    font-weight: 900;
    color: #111;
    line-height: 1;
}

.product-mini-price.is-promo {
    color: var(--product-danger);
}

/* =========================
   RESPONSIVE GLOBAL
   ========================= */

@media screen and (max-width: 1180px) {
    .product-layout {
        grid-template-columns: 1fr;
        gap: 18px;
    }

    .product-right-column {
        position: static;
        top: auto;
    }

    .product-gallery-panel,
    .product-content-panel {
        height: auto;
    }

    .product-gallery-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }

    .product-thumbs {
        order: 2;
        flex-direction: row;
        gap: 10px;
        justify-content: flex-start;
    }

    .product-main-visual {
        order: 1;
        justify-content: center;
        min-height: auto;
        padding: 8px 0;
    }

    .product-main-visual img {
        max-width: 520px;
        max-height: none;
    }

    .product-description-panel,
    .product-recommendation-panel {
        margin-top: 18px;
    }
}

@media screen and (max-width: 1024px) {
    .product-recommendation-grid {
        gap: 16px;
    }

    .product-mini-card {
        flex: 0 0 calc((100% - 32px) / 3);
        min-width: calc((100% - 32px) / 3);
        max-width: calc((100% - 32px) / 3);
    }
}

@media screen and (max-width: 768px) {
    .product-page-wrap {
        margin: 14px auto 26px;
        padding: 0 12px;
    }

    .product-gallery-panel,
    .product-content-panel,
    .product-description-panel,
    .product-recommendation-panel {
        border-radius: 18px;
    }

    .product-gallery-panel {
        padding: 12px;
    }

    .product-thumb {
        width: 54px;
        height: 54px;
        border-radius: 10px;
        flex-shrink: 0;
    }

    .product-main-visual img {
        width: 100%;
        max-width: 100%;
        height: auto;
    }

    .product-content-panel {
        padding: 20px 16px 18px;
    }

    .product-title {
        font-size: 29px;
        line-height: 1.12;
        word-break: break-word;
    }

    .product-title-accent {
        width: 68px;
        height: 7px;
        margin: 12px 0 14px;
    }

    .product-price-old,
    .product-price-current {
        font-size: 17px;
    }

    .product-short-description,
    .product-tech-list li {
        font-size: 14px;
        line-height: 1.6;
    }

    .product-tech {
        padding: 14px 14px;
    }

    .product-description-panel {
        padding: 18px 16px;
    }

    .product-description-content h2,
    .nicotine-help-block h2,
    .product-recommendation-title {
        font-size: 22px;
        line-height: 1.3;
    }

    .product-description-content h3,
    .nicotine-help-block h3 {
        font-size: 18px;
        line-height: 1.35;
    }

    .product-description-content h4,
    .product-description-content h5,
    .nicotine-help-block h4,
    .nicotine-help-block h5 {
        font-size: 16px;
        line-height: 1.35;
    }

    .product-description-content p,
    .product-description-content li,
    .nicotine-help-block p,
    .nicotine-help-block li {
        font-size: 15px;
        line-height: 1.65;
    }

    .purchase-card {
        padding: 12px 14px;
        border-radius: 18px;
    }

    .purchase-main-inline {
        grid-template-columns: 46px minmax(0, 1fr) auto auto;
        gap: 10px;
    }

    .purchase-product-thumb {
        width: 44px;
        height: 44px;
    }

    .purchase-product-title {
        font-size: 14px;
    }

    .purchase-product-brand {
        font-size: 12px;
    }

    .purchase-price-stack {
        min-width: 86px;
    }

    .purchase-old-price {
        font-size: 12px;
    }

    .purchase-final-price {
        font-size: 15px;
    }

    .purchase-qty-wrap {
        width: 78px;
    }

    .purchase-qty-controls {
        height: 36px;
        grid-template-columns: 22px 1fr 22px;
        padding: 0 3px;
    }

    .qty-btn {
        font-size: 20px;
    }

    .qty-input {
        font-size: 14px;
    }

    .purchase-boosters-wrap {
        display: block;
    }

    .purchase-boosters-toggle {
        width: 100%;
    }

    .purchase-boosters-toggle-inner {
        display: flex;
        width: 100%;
        min-width: 0;
        min-height: 52px;
        padding: 0 18px;
        font-size: 18px;
        border-radius: 14px;
    }

    .purchase-boosters-icon {
        font-size: 16px;
    }

    .purchase-boosters-panel {
        padding: 8px 10px;
        border-radius: 18px;
    }

    .purchase-booster-row {
        grid-template-columns: 38px minmax(0, 1fr) auto auto;
        gap: 10px;
        padding: 10px 4px;
    }

    .purchase-booster-thumb {
        width: 36px;
        height: 36px;
    }

    .purchase-booster-title {
        font-size: 13px;
    }

    .purchase-booster-brand {
        font-size: 11px;
    }

    .purchase-booster-price-wrap {
        min-width: 74px;
    }

    .purchase-booster-old-price {
        font-size: 11px;
    }

    .purchase-booster-price {
        font-size: 13px;
    }

    .purchase-booster-qty .purchase-qty-controls {
        width: 78px;
        height: 36px;
        grid-template-columns: 22px 1fr 22px;
        padding: 0 3px;
    }

    .purchase-add-cart {
        min-height: 56px;
        font-size: 18px;
        border-radius: 14px;
    }

    .purchase-reassurance-card {
        padding: 14px;
        border-radius: 18px;
    }

    .purchase-reassurance-item {
        grid-template-columns: 44px minmax(0, 1fr);
        gap: 10px;
    }

    .purchase-reassurance-icon {
        width: 44px;
        height: 44px;
    }

    .purchase-reassurance-icon img {
        width: 28px;
        height: 28px;
    }

    .purchase-reassurance-text h3 {
        font-size: 13px;
    }

    .purchase-reassurance-text p {
        font-size: 12px;
        line-height: 1.5;
    }

    .nicotine-help-image {
        max-width: 100%;
    }

    .nicotine-help-block {
        padding: 18px 16px 0;
    }

    .product-recommendation-panel {
        padding: 18px 10px 16px;
        overflow: hidden;
    }

    .product-recommendation-header {
        justify-content: center;
        text-align: center;
        margin-bottom: 12px;
    }

    .product-recommendation-header > div {
        width: 100%;
        text-align: center;
    }

    .product-recommendation-title {
        font-size: 20px;
        line-height: 1.2;
        margin-bottom: 8px;
    }

    .product-recommendation-subtitle {
        font-size: 12px;
        line-height: 1.35;
        text-align: center;
    }

    .product-recommendation-grid {
        gap: 10px;
        padding-bottom: 4px;
    }

    .product-mini-card {
        flex: 0 0 calc((100% - 10px) / 2);
        min-width: calc((100% - 10px) / 2);
        max-width: calc((100% - 10px) / 2);
        border-radius: 14px;
    }

    .product-mini-image-wrap {
        height: 120px;
        padding: 8px;
    }

    .product-mini-body {
        padding: 10px 8px 12px;
    }

    .product-mini-brand {
        font-size: 11px;
        line-height: 1.2;
        margin-bottom: 4px;
    }

    .product-mini-title {
        font-size: 12px;
        line-height: 1.2;
        margin-bottom: 8px;
        min-height: 30px;
    }

    .product-mini-price-line {
        gap: 6px;
    }

    .product-mini-old-price {
        font-size: 11px;
        line-height: 1.1;
    }

    .product-mini-price {
        font-size: 12px;
        line-height: 1.1;
    }
}

@media screen and (max-width: 480px) {
    .product-page-wrap {
        padding: 0 10px;
    }

    .product-title {
        font-size: 25px;
    }

    .product-content-panel,
    .product-description-panel,
    .product-gallery-panel,
    .product-recommendation-panel {
        padding-left: 14px;
        padding-right: 14px;
    }

    .purchase-main-inline {
        grid-template-columns: 40px minmax(0, 1fr) auto auto;
        gap: 8px;
    }

    .purchase-product-thumb {
        width: 38px;
        height: 38px;
    }

    .purchase-product-title {
        font-size: 13px;
    }

    .purchase-product-brand {
        font-size: 11px;
    }

    .purchase-price-stack {
        min-width: 68px;
    }

    .purchase-old-price {
        font-size: 11px;
    }

    .purchase-final-price {
        font-size: 12px;
    }

    .purchase-qty-wrap {
        width: 72px;
    }

    .purchase-qty-controls {
        height: 34px;
        grid-template-columns: 20px 1fr 20px;
        border-width: 2px;
    }

    .qty-btn {
        font-size: 18px;
    }

    .qty-input {
        font-size: 13px;
    }

    .purchase-boosters-toggle-inner {
        min-height: 50px;
        font-size: 16px;
        padding: 0 16px;
    }

    .purchase-booster-row {
        grid-template-columns: 34px minmax(0, 1fr) auto auto;
        gap: 8px;
    }

    .purchase-booster-thumb {
        width: 32px;
        height: 32px;
    }

    .purchase-booster-title {
        font-size: 12px;
    }

    .purchase-booster-price-wrap {
        min-width: 66px;
    }

    .purchase-booster-price {
        font-size: 12px;
    }

    .purchase-booster-old-price {
        font-size: 10px;
    }

    .purchase-booster-qty .purchase-qty-controls {
        width: 72px;
        height: 34px;
        grid-template-columns: 20px 1fr 20px;
        border-width: 2px;
    }

    .purchase-add-cart {
        font-size: 16px;
    }

    .purchase-reassurance-item {
        grid-template-columns: 40px minmax(0, 1fr);
    }

    .purchase-reassurance-icon {
        width: 40px;
        height: 40px;
    }

    .purchase-reassurance-icon img {
        width: 24px;
        height: 24px;
    }

    .product-recommendation-panel {
        padding: 16px 8px 14px;
    }

    .product-recommendation-title {
        font-size: 18px;
    }

    .product-recommendation-subtitle {
        font-size: 11px;
    }

    .product-recommendation-grid {
        gap: 8px;
    }

    .product-mini-card {
        flex: 0 0 calc((100% - 8px) / 2);
        min-width: calc((100% - 8px) / 2);
        max-width: calc((100% - 8px) / 2);
    }

    .product-mini-image-wrap {
        height: 105px;
        padding: 6px;
    }

    .product-mini-body {
        padding: 8px 6px 10px;
    }

    .product-mini-brand {
        font-size: 10px;
        margin-bottom: 3px;
    }

    .product-mini-title {
        font-size: 11px;
        line-height: 1.15;
        margin-bottom: 6px;
        min-height: 26px;
    }

    .product-mini-old-price {
        font-size: 10px;
    }

    .product-mini-price {
        font-size: 11px;
    }
}
</style>
<main class="product-page-wrap">
    <?php if (!$product): ?>
        <section class="product-not-found">
            <h1 style="font-size:2rem; margin-bottom:10px;">Produit introuvable</h1>
            <p style="color:#6b7280; margin-bottom:20px;">Le produit demandé n'existe pas ou n'est pas actif.</p>
            <a href="collection.php" style="display:inline-flex;align-items:center;justify-content:center;min-height:54px;padding:0 20px;border-radius:999px;background:#111;color:#fff;text-decoration:none;font-weight:800;">
                Retour à la collection
            </a>
        </section>
    <?php else: ?>
        <?php
        $prixRegulier = (float) ($product['prix_regulier'] ?? 0);
        $prixPromo = isset($product['prix_promo']) && $product['prix_promo'] !== null ? (float) $product['prix_promo'] : null;
        $prixFinal = $prixPromo ?: $prixRegulier;

        $galleryImages = [];
        $mainImage = buildProductImagePath(isset($product['image_principale']) ? (string) $product['image_principale'] : null);
        $galleryImages[] = $mainImage;

        $descriptionCourte = trim((string) ($product['description_courte'] ?? ''));
        $descriptionLongue = trim((string) ($product['description_longue'] ?? ''));
        $descriptionBlocks = splitDescriptionParagraphs($descriptionLongue);
        $descriptionHtml = sanitizeProductDescriptionHtml($descriptionLongue);

        $techItems = [];

        $marque = trim((string) ($product['marque'] ?? ''));
        if ($marque !== '') {
            $techItems[] = ['label' => 'Marque', 'value' => $marque];
        }

        $gamme = trim((string) ($product['gamme'] ?? ''));
        if ($gamme !== '') {
            $techItems[] = ['label' => 'Gamme', 'value' => $gamme];
        }

        $ratio = getFirstAttributeValue($attributes, 'ratio_pg_vg');
        if ($ratio !== '') {
            $techItems[] = ['label' => 'PG/VG', 'value' => $ratio];
        }

        $nicotine = getFirstAttributeValue($attributes, 'nicotine_mg');
        if ($nicotine !== '') {
            $techItems[] = ['label' => 'Tx nicotine', 'value' => $nicotine];
        }

        $contenance = getFirstAttributeValue($attributes, 'contenance_ml');
        if ($contenance !== '') {
            $techItems[] = ['label' => 'Contenance', 'value' => formatContenanceDisplay($contenance)];
        }

        $typeProduit = getFirstAttributeValue($attributes, 'type_produit');
        if ($typeProduit !== '') {
            $techItems[] = ['label' => 'Type de produit', 'value' => $typeProduit];
        }

        $saveurDirecte = getJoinedAttributeValue($attributes, 'saveur');
        $saveurFamille = getJoinedAttributeValue($attributes, 'saveur_famille');

        if ($saveurDirecte !== '') {
            $techItems[] = ['label' => 'Saveurs', 'value' => $saveurDirecte];
        } elseif ($saveurFamille !== '') {
            $techItems[] = ['label' => 'Famille', 'value' => $saveurFamille];
        }

        $showBoosters = !empty($boosters);

        $contenanceValue = extractMlValue($contenance);
        $typeProduitNormalized = normalizeText($typeProduit);
        $categorieNomNormalized = normalizeText((string) ($product['categorie_nom'] ?? ''));
        $nomProduitNormalized = normalizeText((string) ($product['nom'] ?? ''));

        $isEliquideProduct =
            strpos($typeProduitNormalized, 'liquide') !== false
            || strpos($typeProduitNormalized, 'e-liquide') !== false
            || strpos($typeProduitNormalized, 'eliquide') !== false
            || strpos($categorieNomNormalized, 'e-liquide') !== false
            || strpos($categorieNomNormalized, 'eliquide') !== false
            || strpos($nomProduitNormalized, 'e-liquide') !== false
            || strpos($nomProduitNormalized, 'eliquide') !== false;

        $showNicotineHelp = $isEliquideProduct && in_array($contenanceValue, [50, 100], true);
        ?>
        <section class="product-layout">
            <div class="product-left-column">
                <div class="product-gallery-panel">
                    <div class="product-gallery-grid">
                        <div class="product-thumbs">
                            <?php foreach ($galleryImages as $index => $imagePath): ?>
                                <button
                                    type="button"
                                    class="product-thumb <?= $index === 0 ? 'is-active' : ''; ?>"
                                    data-image="<?= e($imagePath); ?>"
                                    aria-label="Voir l'image <?= $index + 1; ?>"
                                >
                                    <img src="<?= e($imagePath); ?>" alt="<?= e($product['nom'] ?? 'Produit'); ?>" loading="lazy" decoding="async">
                                </button>
                            <?php endforeach; ?>
                        </div>

                        <div class="product-main-visual">
                            <img
                                id="mainProductImage"
                                src="<?= e($galleryImages[0]); ?>"
                                alt="<?= e($product['nom'] ?? 'Produit'); ?>"
                                fetchpriority="high"
                                decoding="async"
                            >
                        </div>
                    </div>
                </div>
            </div>

            <div class="product-right-column">
                <div class="product-content-panel">
                    <h1 class="product-title"><?= e($product['nom'] ?? 'Produit'); ?></h1>
                    <div class="product-title-accent"></div>

                    <div class="product-price-inline">
                        <?php if ($prixPromo): ?>
                            <span class="product-price-old"><?= e(formatAr($prixRegulier)); ?></span>
                            <span class="product-price-current"><?= e(formatAr($prixFinal)); ?></span>
                        <?php else: ?>
                            <span class="product-price-current no-promo"><?= e(formatAr($prixFinal)); ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if ($descriptionCourte !== ''): ?>
                        <p class="product-short-description"><?= nl2br(e($descriptionCourte)); ?></p>
                    <?php endif; ?>

                    <?php if (!empty($techItems)): ?>
                        <div class="product-tech">
                            <div class="product-tech-title">Fiche technique</div>
                            <ul class="product-tech-list">
                                <?php foreach ($techItems as $item): ?>
                                    <li><strong><?= e($item['label']); ?> :</strong> <?= e($item['value']); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>

                <section class="product-purchase-bar">
                    <form method="post" action="panier.php" class="purchase-bundle-form">
                        <input type="hidden" name="action" value="add_bundle">
                        <input type="hidden" name="main_product_id" value="<?= (int) $product['id']; ?>">

                        <div class="purchase-card">
                            <div class="purchase-main-inline">
                                <div class="purchase-product-thumb">
                                    <img src="<?= e($galleryImages[0]); ?>" alt="<?= e($product['nom'] ?? 'Produit'); ?>" loading="lazy" decoding="async">
                                </div>

                                <div class="purchase-product-meta">
                                    <div class="purchase-product-title"><?= e($product['nom'] ?? 'Produit'); ?></div>
                                    <?php if (!empty($product['marque'])): ?>
                                        <div class="purchase-product-brand"><?= e($product['marque']); ?></div>
                                    <?php endif; ?>
                                    <?php if ($nicotine !== '' && isZeroNicotineValue($nicotine)): ?>
                                        <div class="purchase-product-note">Liquide non nicotiné</div>
                                    <?php endif; ?>
                                </div>

                                <div class="purchase-price-stack">
                                    <div class="purchase-price-line">
                                        <?php if ($prixPromo): ?>
                                            <span class="purchase-old-price"><?= e(formatAr($prixRegulier)); ?></span>
                                            <span class="purchase-final-price is-promo"><?= e(formatAr($prixFinal)); ?></span>
                                        <?php else: ?>
                                            <span class="purchase-final-price"><?= e(formatAr($prixFinal)); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="purchase-qty-wrap">
                                    <div class="purchase-qty-controls">
                                        <button type="button" class="qty-btn" data-target="qty_main" data-action="minus" aria-label="Diminuer">−</button>
                                        <input type="number" name="qty_main" id="qty_main" class="qty-input" value="1" min="1" inputmode="numeric">
                                        <button type="button" class="qty-btn" data-target="qty_main" data-action="plus" aria-label="Augmenter">+</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($showBoosters): ?>
                            <div class="purchase-boosters-wrap purchase-boosters is-open" id="boostersBlock">
                                <button type="button" class="purchase-boosters-toggle" id="boostersToggle" aria-expanded="true" aria-controls="boostersContent">
                                    <span class="purchase-boosters-toggle-inner">
                                        <span>Besoin de nicotine ?</span>
                                        <span class="purchase-boosters-icon">⌃</span>
                                    </span>
                                </button>

                                <div class="purchase-boosters-panel" id="boostersContent">
                                    <?php foreach ($boosters as $booster): ?>
                                        <?php
                                        $boosterPrixRegulier = (float) ($booster['prix_regulier'] ?? 0);
                                        $boosterPrixPromo = isset($booster['prix_promo']) && $booster['prix_promo'] !== null ? (float) $booster['prix_promo'] : null;
                                        $boosterPrixFinal = $boosterPrixPromo ?: $boosterPrixRegulier;
                                        $boosterImage = buildProductImagePath(isset($booster['image_principale']) ? (string) $booster['image_principale'] : null);
                                        $qtyField = 'qty_booster_' . (int) $booster['id'];
                                        ?>
                                        <input type="hidden" name="booster_ids[]" value="<?= (int) $booster['id']; ?>">

                                        <div class="purchase-booster-row">
                                            <div class="purchase-booster-thumb">
                                                <img src="<?= e($boosterImage); ?>" alt="<?= e($booster['nom'] ?? 'Booster'); ?>" loading="lazy" decoding="async">
                                            </div>

                                            <div class="purchase-booster-meta">
                                                <div class="purchase-booster-title"><?= e($booster['nom'] ?? 'Booster'); ?></div>
                                                <?php if (!empty($booster['marque'])): ?>
                                                    <div class="purchase-booster-brand"><?= e($booster['marque']); ?></div>
                                                <?php endif; ?>
                                            </div>

                                            <div class="purchase-booster-price-wrap">
                                                <div class="purchase-booster-price-line">
                                                    <?php if ($boosterPrixPromo): ?>
                                                        <span class="purchase-booster-old-price"><?= e(formatAr($boosterPrixRegulier)); ?></span>
                                                        <span class="purchase-booster-price is-promo"><?= e(formatAr($boosterPrixFinal)); ?></span>
                                                    <?php else: ?>
                                                        <span class="purchase-booster-price"><?= e(formatAr($boosterPrixFinal)); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div class="purchase-booster-qty">
                                                <div class="purchase-qty-controls">
                                                    <button type="button" class="qty-btn" data-target="<?= e($qtyField); ?>" data-action="minus" aria-label="Diminuer">−</button>
                                                    <input type="number" name="booster_qtys[<?= (int) $booster['id']; ?>]" id="<?= e($qtyField); ?>" class="qty-input" value="0" min="0" inputmode="numeric">
                                                    <button type="button" class="qty-btn" data-target="<?= e($qtyField); ?>" data-action="plus" aria-label="Augmenter">+</button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="purchase-footer">
                            <button type="submit" class="purchase-add-cart">
                                Ajouter au panier
                            </button>
                        </div>

                        <div class="purchase-reassurance-card" aria-label="Réassurances">
                            <div class="purchase-reassurance-list">
                                <div class="purchase-reassurance-item">
                                    <div class="purchase-reassurance-icon">
                                        <img src="img/produit certifié-icon.svg" alt="Produit authentique" loading="lazy" decoding="async">
                                    </div>
                                    <div class="purchase-reassurance-text">
                                        <h3>100% authentique</h3>
                                        <p>Fioles premium certifiées importées de France.</p>
                                    </div>
                                </div>

                                <div class="purchase-reassurance-item">
                                    <div class="purchase-reassurance-icon">
                                        <img src="img/livraison-icon.svg" alt="Livraison express" loading="lazy" decoding="async">
                                    </div>
                                    <div class="purchase-reassurance-text">
                                        <h3>Livraison express</h3>
                                        <p>Le jour même sur Antananarivo.</p>
                                    </div>
                                </div>

                                <div class="purchase-reassurance-item">
                                    <div class="purchase-reassurance-icon">
                                        <img src="img/paiement-icon.svg" alt="Paiement flexible" loading="lazy" decoding="async">
                                    </div>
                                    <div class="purchase-reassurance-text">
                                        <h3>Paiement flexible</h3>
                                        <p>MVola, Orange Money, Airtel Money & Cash.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </section>
            </div>
        </section>

        <?php if ($descriptionLongue !== '' || $showNicotineHelp): ?>
            <section class="product-description-panel">
                <div class="product-description-content">
                    <?php if ($descriptionLongue !== '' && descriptionLooksLikeHtml($descriptionLongue)): ?>
                        <?= $descriptionHtml; ?>
                    <?php elseif (!empty($descriptionBlocks)): ?>
                        <?php foreach ($descriptionBlocks as $paragraph): ?>
                            <p><?= nl2br(e($paragraph)); ?></p>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if ($showNicotineHelp): ?>
                    <div class="nicotine-help-block">
                        <h2>Comment ajouter de la nicotine dans un e-liquide ?</h2>

                        <p>
                            Un e-liquide sans nicotine peut facilement être personnalisé en y ajoutant des boosters de nicotine.
                            Un booster est un petit flacon, généralement de <strong>10 ml</strong>, contenant une forte concentration
                            de nicotine, souvent à <strong>20 mg/ml</strong>.
                        </p>

                        <p><strong>👉 Pour l’utiliser, il suffit de :</strong></p>
                        <ul>
                            <li>Ouvrir votre flacon d’e-liquide, souvent en grand format type 50 ml ou 100 ml</li>
                            <li>Ajouter le nombre de boosters souhaité selon le dosage désiré</li>
                            <li>Refermer puis bien secouer le mélange pour homogénéiser</li>
                        </ul>

                        <p><strong>💡 Exemple :</strong></p>
                        <div class="nicotine-help-image">
                            <img src="img/produits/dosage-nicotine-50ml.jpg" alt="Exemple de dosage nicotine e-liquide 50ml" loading="lazy" decoding="async">
                        </div>

                        <p><strong>⚠️ Important :</strong></p>
                        <ul>
                            <li>Plus vous ajoutez de boosters, plus le goût peut légèrement diminuer</li>
                            <li>Respectez les dosages recommandés pour une expérience optimale</li>
                        </ul>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php if (!empty($relatedSameLine)): ?>
            <section class="product-recommendation-panel">
                <div class="product-recommendation-header">
                    <div>
                        <h2 class="product-recommendation-title"><?= e($relatedSameLineTitle); ?></h2>
                        <p class="product-recommendation-subtitle">Découvrez d’autres références proches du produit que vous consultez.</p>
                    </div>
                </div>

                <div class="product-recommendation-grid">
                    <?php foreach ($relatedSameLine as $item): ?>
                        <?php
                        $itemPrixRegulier = (float) ($item['prix_regulier'] ?? 0);
                        $itemPrixPromo = isset($item['prix_promo']) && $item['prix_promo'] !== null ? (float) $item['prix_promo'] : null;
                        $itemPrixFinal = $itemPrixPromo ?: $itemPrixRegulier;
                        $itemImage = buildProductImagePath(isset($item['image_principale']) ? (string) $item['image_principale'] : null);
                        $itemSlug = trim((string) ($item['slug'] ?? ''));
                        ?>
                        <article class="product-mini-card">
                            <a class="product-mini-link" href="produit.php?slug=<?= e($itemSlug); ?>">
                                <div class="product-mini-image-wrap">
                                    <img src="<?= e($itemImage); ?>" alt="<?= e($item['nom'] ?? 'Produit'); ?>" loading="lazy" decoding="async">
                                </div>
                                <div class="product-mini-body">
                                    <?php if (!empty($item['marque'])): ?>
                                        <p class="product-mini-brand"><?= e($item['marque']); ?></p>
                                    <?php endif; ?>
                                    <h3 class="product-mini-title"><?= e($item['nom'] ?? 'Produit'); ?></h3>
                                    <div class="product-mini-price-line">
                                        <?php if ($itemPrixPromo): ?>
                                            <span class="product-mini-old-price"><?= e(formatAr($itemPrixRegulier)); ?></span>
                                            <span class="product-mini-price is-promo"><?= e(formatAr($itemPrixFinal)); ?></span>
                                        <?php else: ?>
                                            <span class="product-mini-price"><?= e(formatAr($itemPrixFinal)); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if (!empty($relatedSimilar)): ?>
            <section class="product-recommendation-panel">
                <div class="product-recommendation-header">
                    <div>
                        <h2 class="product-recommendation-title"><?= e($relatedSimilarTitle); ?></h2>
                        <p class="product-recommendation-subtitle">Des produits proches, sélectionnés selon le même univers et le même usage.</p>
                    </div>
                </div>

                <div class="product-recommendation-grid">
                    <?php foreach ($relatedSimilar as $item): ?>
                        <?php
                        $itemPrixRegulier = (float) ($item['prix_regulier'] ?? 0);
                        $itemPrixPromo = isset($item['prix_promo']) && $item['prix_promo'] !== null ? (float) $item['prix_promo'] : null;
                        $itemPrixFinal = $itemPrixPromo ?: $itemPrixRegulier;
                        $itemImage = buildProductImagePath(isset($item['image_principale']) ? (string) $item['image_principale'] : null);
                        $itemSlug = trim((string) ($item['slug'] ?? ''));
                        ?>
                        <article class="product-mini-card">
                            <a class="product-mini-link" href="produit.php?slug=<?= e($itemSlug); ?>">
                                <div class="product-mini-image-wrap">
                                    <img src="<?= e($itemImage); ?>" alt="<?= e($item['nom'] ?? 'Produit'); ?>" loading="lazy" decoding="async">
                                </div>
                                <div class="product-mini-body">
                                    <?php if (!empty($item['marque'])): ?>
                                        <p class="product-mini-brand"><?= e($item['marque']); ?></p>
                                    <?php endif; ?>
                                    <h3 class="product-mini-title"><?= e($item['nom'] ?? 'Produit'); ?></h3>
                                    <div class="product-mini-price-line">
                                        <?php if ($itemPrixPromo): ?>
                                            <span class="product-mini-old-price"><?= e(formatAr($itemPrixRegulier)); ?></span>
                                            <span class="product-mini-price is-promo"><?= e(formatAr($itemPrixFinal)); ?></span>
                                        <?php else: ?>
                                            <span class="product-mini-price"><?= e(formatAr($itemPrixFinal)); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    <?php endif; ?>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const mainImage = document.getElementById('mainProductImage');
    const thumbs = document.querySelectorAll('.product-thumb');

    thumbs.forEach(function (thumb) {
        thumb.addEventListener('click', function () {
            const newImage = thumb.getAttribute('data-image');

            if (!newImage || !mainImage) {
                return;
            }

            mainImage.src = newImage;

            thumbs.forEach(function (item) {
                item.classList.remove('is-active');
            });

            thumb.classList.add('is-active');
        });
    });

    const qtyButtons = document.querySelectorAll('.qty-btn');

    qtyButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const targetId = button.getAttribute('data-target');
            const action = button.getAttribute('data-action');
            const input = targetId ? document.getElementById(targetId) : null;

            if (!input) {
                return;
            }

            const min = parseInt(input.getAttribute('min') || '0', 10);
            const current = parseInt(input.value || String(min), 10);

            if (action === 'minus') {
                input.value = String(Math.max(min, current - 1));
            } else if (action === 'plus') {
                input.value = String(current + 1);
            }
        });
    });

    const qtyInputs = document.querySelectorAll('.qty-input');

    qtyInputs.forEach(function (input) {
        input.addEventListener('input', function () {
            const min = parseInt(input.getAttribute('min') || '0', 10);
            const numericValue = parseInt(input.value || String(min), 10);

            if (Number.isNaN(numericValue) || numericValue < min) {
                input.value = String(min);
            }
        });

        input.addEventListener('blur', function () {
            const min = parseInt(input.getAttribute('min') || '0', 10);
            const numericValue = parseInt(input.value || String(min), 10);

            if (Number.isNaN(numericValue) || numericValue < min) {
                input.value = String(min);
            }
        });
    });

    const boostersBlock = document.getElementById('boostersBlock');
    const boostersToggle = document.getElementById('boostersToggle');

    if (boostersBlock && boostersToggle) {
        boostersToggle.addEventListener('click', function () {
            boostersBlock.classList.toggle('is-open');
            const isOpen = boostersBlock.classList.contains('is-open');
            boostersToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }
});
</script>

<?php include 'footer.php'; ?>
