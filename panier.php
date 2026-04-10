<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';
require_once 'add_to_cart_form.php';

/**
 * @param mixed $value
 */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function buildSafeRedirectTarget(?string $target): string
{
    $fallback = 'panier.php';
    $value = trim((string) $target);

    if ($value === '') {
        return $fallback;
    }

    if (preg_match('/^https?:\/\//i', $value) === 1) {
        return $fallback;
    }

    if (strpos($value, '/') === 0 || strpos($value, '..') !== false) {
        return $fallback;
    }

    return $value;
}

function getCartTotalQuantity(array $cart): int
{
    $count = 0;
    foreach ($cart as $item) {
        if (!is_array($item)) {
            continue;
        }

        $count += max(1, (int) ($item['qty'] ?? 1));
    }

    return $count;
}

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

function normalizeVariantKey(string $value): string
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
    $value = strtr($value, $replace);
    $value = preg_replace('/[^a-z0-9]+/i', '', $value) ?? '';
    return trim((string) $value);
}

function parsePriceValue(string $raw): ?float
{
    $value = trim($raw);
    if ($value === '') {
        return null;
    }

    $value = str_replace(['Ar', 'ariary', ' '], '', $value);
    $value = str_replace(',', '.', $value);
    if (!is_numeric($value)) {
        return null;
    }

    $parsed = (float) $value;
    return $parsed >= 0 ? $parsed : null;
}

/**
 * @param array<int, array{attribut_nom: mixed, attribut_valeur: mixed}> $rows
 * @return array<int, array{label: string, prix_regulier: float, prix_promo: float|null, prix_final: float}>
 */
function buildNicotineVariantsFromRows(array $rows, float $baseRegular, ?float $basePromo): array
{
    $attributes = [];
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

    $labels = [];
    foreach ($attributes['nicotine_mg'] ?? [] as $rawValue) {
        $parts = preg_split('/\s*(?:,|;|\||\/)\s*/', (string) $rawValue) ?: [];
        foreach ($parts as $part) {
            $label = trim((string) $part);
            if ($label === '') {
                continue;
            }
            if (preg_match('/mg/i', $label) !== 1 && preg_match('/^\d+(\.\d+)?$/', $label) === 1) {
                $label .= 'mg';
            }
            $labels[] = $label;
        }
    }
    $labels = array_values(array_unique($labels));
    if (count($labels) <= 1) {
        return [];
    }

    $regularOverrides = [];
    $promoOverrides = [];

    foreach ($attributes as $attrName => $attrValues) {
        $attrKey = normalizeVariantKey($attrName);
        foreach ($attrValues as $attrValue) {
            if ($attrKey === 'nicotineprix' || $attrKey === 'prixnicotine' || $attrKey === 'nicotineprixpromo' || $attrKey === 'prixpromonicotine') {
                $pairs = preg_split('/\s*(?:\||;|,)\s*/', (string) $attrValue) ?: [];
                foreach ($pairs as $pair) {
                    $segments = preg_split('/\s*(?:=|:)\s*/', $pair, 2) ?: [];
                    if (count($segments) !== 2) {
                        continue;
                    }
                    $key = normalizeVariantKey((string) $segments[0]);
                    $price = parsePriceValue((string) $segments[1]);
                    if ($key === '' || $price === null) {
                        continue;
                    }
                    if ($attrKey === 'nicotineprixpromo' || $attrKey === 'prixpromonicotine') {
                        $promoOverrides[$key] = $price;
                    } else {
                        $regularOverrides[$key] = $price;
                    }
                }
            }
        }

        if (strpos($attrKey, 'nicotineprix') === 0 || strpos($attrKey, 'prixnicotine') === 0) {
            if (preg_match('/(?:nicotineprix|prixnicotine)(?:promo)?(.+)/', $attrKey, $matches) !== 1) {
                continue;
            }

            $suffix = trim((string) ($matches[1] ?? ''));
            $price = parsePriceValue((string) ($attrValues[0] ?? ''));
            if ($suffix === '' || $price === null) {
                continue;
            }

            if (strpos($attrKey, 'promo') !== false) {
                $promoOverrides[$suffix] = $price;
            } else {
                $regularOverrides[$suffix] = $price;
            }
        }
    }

    $variants = [];
    foreach ($labels as $label) {
        $key = normalizeVariantKey($label);
        $regular = $regularOverrides[$key] ?? $baseRegular;
        $promo = $promoOverrides[$key] ?? $basePromo;
        $variants[] = [
            'label' => $label,
            'prix_regulier' => $regular,
            'prix_promo' => $promo,
            'prix_final' => $promo !== null ? $promo : $regular,
        ];
    }

    return $variants;
}

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$user = null;
$userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
if ($userId > 0) {
    $userStmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE id = ? LIMIT 1');
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/**
 * @param array<string, mixed>|null $userData
 * @return array<int, array<string, string>>
 */
function extractSavedAddresses(?array $userData): array
{
    if (!$userData) {
        return [];
    }

    $addresses = [];
    for ($i = 1; $i <= 2; $i++) {
        $line = trim((string) ($userData['adresse_' . $i] ?? ''));
        $quartier = trim((string) ($userData['quartier_' . $i] ?? ''));
        $ville = trim((string) ($userData['ville_' . $i] ?? ''));

        if ($line === '') {
            continue;
        }

        $addresses[] = [
            'key' => 'addr' . $i,
            'label' => $i === 1 ? 'Adresse Principale' : 'Deuxième Adresse',
            'line' => $line,
            'quartier' => $quartier,
            'ville' => $ville,
        ];
    }

    return $addresses;
}

$savedAddresses = extractSavedAddresses($user);
$selectedAddressKey = (string) ($_SESSION['selected_delivery_address'] ?? (!empty($savedAddresses) ? $savedAddresses[0]['key'] : ''));
$shippingMessage = '';

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if ($method === 'POST') {
    $action = trim((string) ($_POST['action'] ?? ''));
    $isAjaxRequest = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

    if ($action === 'add_bundle') {
        $mainProductId = sanitizePositiveInt($_POST['main_product_id'] ?? 0, 0);
        $qtyMain = max(1, sanitizePositiveInt($_POST['qty_main'] ?? 1, 1));
        $redirectTo = buildSafeRedirectTarget($_POST['redirect_to'] ?? ($_SERVER['HTTP_REFERER'] ?? ''));
        $requestedNicotineVariant = trim((string) ($_POST['nicotine_variant'] ?? ''));
        $selectedVariant = null;

        if ($mainProductId > 0) {
            $productStmt = $pdo->prepare('SELECT id, prix_regulier, prix_promo FROM produits WHERE id = :id LIMIT 1');
            $productStmt->execute(['id' => $mainProductId]);
            $productRow = $productStmt->fetch(PDO::FETCH_ASSOC) ?: null;

            if ($productRow) {
                $baseRegular = (float) ($productRow['prix_regulier'] ?? 0);
                $basePromo = isset($productRow['prix_promo']) && $productRow['prix_promo'] !== null
                    ? (float) $productRow['prix_promo']
                    : null;

                $attrStmt = $pdo->prepare('SELECT attribut_nom, attribut_valeur FROM produit_attributs WHERE produit_id = :id');
                $attrStmt->execute(['id' => $mainProductId]);
                $variantRows = $attrStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

                $variants = buildNicotineVariantsFromRows($variantRows, $baseRegular, $basePromo);
                if (!empty($variants) && $requestedNicotineVariant !== '') {
                    $requestedKey = normalizeVariantKey($requestedNicotineVariant);
                    foreach ($variants as $variant) {
                        if (normalizeVariantKey((string) $variant['label']) === $requestedKey) {
                            $selectedVariant = $variant;
                            break;
                        }
                    }
                }
            }

            $bundleKey = 'main_' . $mainProductId;
            if ($selectedVariant) {
                $bundleKey .= '_' . normalizeVariantKey((string) $selectedVariant['label']);
            }

            if (!isset($_SESSION['cart'][$bundleKey])) {
                $_SESSION['cart'][$bundleKey] = [
                    'product_id' => $mainProductId,
                    'qty' => 0,
                    'boosters' => [],
                ];
            }

            if ($selectedVariant) {
                $_SESSION['cart'][$bundleKey]['variant'] = [
                    'type' => 'nicotine_mg',
                    'label' => (string) $selectedVariant['label'],
                    'prix_regulier' => (float) $selectedVariant['prix_regulier'],
                    'prix_promo' => $selectedVariant['prix_promo'] !== null ? (float) $selectedVariant['prix_promo'] : null,
                ];
            }

            $_SESSION['cart'][$bundleKey]['qty'] += $qtyMain;

            $boosterQtys = $_POST['booster_qtys'] ?? [];
            if (is_array($boosterQtys)) {
                foreach ($boosterQtys as $boosterId => $qty) {
                    $boosterIdInt = sanitizePositiveInt($boosterId, 0);
                    $qtyInt = sanitizePositiveInt($qty, 0);

                    if ($boosterIdInt <= 0 || $qtyInt <= 0) {
                        continue;
                    }

                    if (!isset($_SESSION['cart'][$bundleKey]['boosters'][$boosterIdInt])) {
                        $_SESSION['cart'][$bundleKey]['boosters'][$boosterIdInt] = 0;
                    }

                    $_SESSION['cart'][$bundleKey]['boosters'][$boosterIdInt] += $qtyInt;
                }
            }
        }

        if ($isAjaxRequest) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'success' => true,
                'message' => 'Produit ajouté au panier.',
                'cart_count' => getCartTotalQuantity($_SESSION['cart']),
            ]);
            exit;
        }

        $separator = strpos($redirectTo, '?') === false ? '?' : '&';
        header('Location: ' . $redirectTo . $separator . 'added=1');
        exit;
    }

    if ($action === 'remove_item') {
        $itemKey = trim((string) ($_POST['item_key'] ?? ''));
        if ($itemKey !== '' && isset($_SESSION['cart'][$itemKey])) {
            unset($_SESSION['cart'][$itemKey]);
        }

        header('Location: panier.php?removed=1');
        exit;
    }

    if ($action === 'remove_booster') {
        $itemKey = trim((string) ($_POST['item_key'] ?? ''));
        $boosterId = sanitizePositiveInt($_POST['booster_id'] ?? 0, 0);

        if ($itemKey !== '' && $boosterId > 0 && isset($_SESSION['cart'][$itemKey]['boosters'][$boosterId])) {
            unset($_SESSION['cart'][$itemKey]['boosters'][$boosterId]);
        }

        header('Location: panier.php');
        exit;
    }

    if ($action === 'choose_delivery_address') {
        $selected = trim((string) ($_POST['delivery_address'] ?? ''));
        if ($selected !== '') {
            $_SESSION['selected_delivery_address'] = $selected;
        }
        header('Location: panier.php');
        exit;
    }

    if ($action === 'save_delivery_address') {
        $line = trim((string) ($_POST['line'] ?? ''));
        $quartier = trim((string) ($_POST['quartier'] ?? ''));
        $ville = trim((string) ($_POST['ville'] ?? ''));
        $saveForFuture = isset($_POST['save_for_future']) && $_POST['save_for_future'] === '1';

        if ($line === '') {
            $_SESSION['shipping_message'] = 'Veuillez renseigner une adresse valide.';
            header('Location: panier.php');
            exit;
        }

        $_SESSION['temp_delivery_address'] = [
            'key' => 'temp',
            'label' => 'Adresse de cette commande',
            'line' => $line,
            'quartier' => $quartier,
            'ville' => $ville,
        ];
        $_SESSION['selected_delivery_address'] = 'temp';

        if ($userId > 0 && $saveForFuture) {
            $slot = empty($user['adresse_1']) ? 1 : 2;
            if (!empty($user['adresse_1']) && !empty($user['adresse_2'])) {
                $slot = 1;
            }

            $stmt = $pdo->prepare("UPDATE utilisateurs SET adresse_{$slot} = ?, quartier_{$slot} = ?, ville_{$slot} = ? WHERE id = ?");
            $stmt->execute([$line, $quartier, $ville, $userId]);
            $_SESSION['shipping_message'] = 'Adresse enregistrée dans votre profil.';
        } else {
            $_SESSION['shipping_message'] = 'Adresse utilisée pour cette commande.';
        }

        header('Location: panier.php');
        exit;
    }

    if ($action === 'update_contact' && $userId > 0) {
        $telephone = trim((string) ($_POST['telephone'] ?? ''));
        $stmt = $pdo->prepare('UPDATE utilisateurs SET telephone = ? WHERE id = ?');
        $stmt->execute([$telephone, $userId]);
        $_SESSION['shipping_message'] = 'Coordonnées mises à jour.';
        header('Location: panier.php');
        exit;
    }

    if ($action === 'place_order') {
        if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
            $_SESSION['shipping_message'] = 'Votre panier est vide.';
            header('Location: panier.php');
            exit;
        }

        $currentUser = $user;
        $currentAddresses = extractSavedAddresses($currentUser);
        $currentSelectedAddressKey = (string) ($_SESSION['selected_delivery_address'] ?? (!empty($currentAddresses) ? $currentAddresses[0]['key'] : ''));
        $currentAddress = null;

        if (
            $currentSelectedAddressKey === 'temp'
            && isset($_SESSION['temp_delivery_address'])
            && is_array($_SESSION['temp_delivery_address'])
        ) {
            $currentAddress = $_SESSION['temp_delivery_address'];
        }

        if ($currentAddress === null) {
            foreach ($currentAddresses as $address) {
                if ($address['key'] === $currentSelectedAddressKey) {
                    $currentAddress = $address;
                    break;
                }
            }
        }

        if ($currentAddress === null) {
            $_SESSION['shipping_message'] = 'Veuillez d’abord renseigner une adresse de livraison.';
            header('Location: panier.php');
            exit;
        }

        if ($userId <= 0) {
            $_SESSION['shipping_message'] = 'Veuillez vous connecter pour valider la commande.';
            header('Location: compte.php');
            exit;
        }

        $cartItemsForOrder = $_SESSION['cart'];
        $productIdsForOrder = [];
        $boosterIdsForOrder = [];

        foreach ($cartItemsForOrder as $cartItem) {
            $productId = (int) ($cartItem['product_id'] ?? 0);
            if ($productId > 0) {
                $productIdsForOrder[] = $productId;
            }

            $boosters = $cartItem['boosters'] ?? [];
            if (is_array($boosters)) {
                foreach ($boosters as $boosterId => $qty) {
                    if ((int) $boosterId > 0 && (int) $qty > 0) {
                        $boosterIdsForOrder[] = (int) $boosterId;
                    }
                }
            }
        }

        $orderProductMap = [];
        if (!empty($productIdsForOrder)) {
            $uniqueProductIds = array_values(array_unique($productIdsForOrder));
            $placeholders = implode(',', array_fill(0, count($uniqueProductIds), '?'));
            $stmt = $pdo->prepare("SELECT id, nom, prix_regulier, prix_promo FROM produits WHERE id IN ($placeholders)");
            $stmt->execute($uniqueProductIds);

            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $orderProductMap[(int) $row['id']] = $row;
            }
        }

        $orderBoosterMap = [];
        if (!empty($boosterIdsForOrder)) {
            $uniqueBoosterIds = array_values(array_unique($boosterIdsForOrder));
            $placeholders = implode(',', array_fill(0, count($uniqueBoosterIds), '?'));
            $stmt = $pdo->prepare("SELECT id, nom, prix_regulier, prix_promo FROM produits WHERE id IN ($placeholders)");
            $stmt->execute($uniqueBoosterIds);

            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $orderBoosterMap[(int) $row['id']] = $row;
            }
        }

        $orderLines = [];
        $orderTotal = 0.0;

        foreach ($cartItemsForOrder as $cartItem) {
            $productId = (int) ($cartItem['product_id'] ?? 0);
            $qty = max(1, (int) ($cartItem['qty'] ?? 1));
            $product = $orderProductMap[$productId] ?? null;

            if (!$product) {
                continue;
            }

            $price = isset($product['prix_promo']) && $product['prix_promo'] !== null
                ? (float) $product['prix_promo']
                : (float) $product['prix_regulier'];
            $variantLabel = '';
            if (isset($cartItem['variant']) && is_array($cartItem['variant'])) {
                $variantLabel = trim((string) ($cartItem['variant']['label'] ?? ''));
                if (isset($cartItem['variant']['prix_promo']) && $cartItem['variant']['prix_promo'] !== null) {
                    $price = (float) $cartItem['variant']['prix_promo'];
                } elseif (isset($cartItem['variant']['prix_regulier'])) {
                    $price = (float) $cartItem['variant']['prix_regulier'];
                }
            }

            $lineTotal = $price * $qty;
            $orderTotal += $lineTotal;

            $lineBoosters = [];
            if (!empty($cartItem['boosters']) && is_array($cartItem['boosters'])) {
                foreach ($cartItem['boosters'] as $boosterId => $boosterQty) {
                    $boosterIdInt = (int) $boosterId;
                    $boosterQtyInt = (int) $boosterQty;

                    if ($boosterQtyInt <= 0 || !isset($orderBoosterMap[$boosterIdInt])) {
                        continue;
                    }

                    $booster = $orderBoosterMap[$boosterIdInt];
                    $boosterPrice = isset($booster['prix_promo']) && $booster['prix_promo'] !== null
                        ? (float) $booster['prix_promo']
                        : (float) $booster['prix_regulier'];

                    $boosterLine = $boosterPrice * $boosterQtyInt;
                    $orderTotal += $boosterLine;

                    $lineBoosters[] = [
                        'name' => (string) ($booster['nom'] ?? 'Booster'),
                        'qty' => $boosterQtyInt,
                        'line_total' => $boosterLine,
                    ];
                }
            }

            $orderLines[] = [␊
                'name' => (string) ($product['nom'] ?? 'Produit') . ($variantLabel !== '' ? ' (' . $variantLabel . ')' : ''),
                'qty' => $qty,␊
                'line_total' => $lineTotal,␊
                'boosters' => $lineBoosters,␊
            ];␊
        }␊

        if (empty($orderLines)) {
            $_SESSION['shipping_message'] = 'Impossible de valider la commande avec les produits actuels.';
            header('Location: panier.php');
            exit;
        }

        $orderNumber = 'CMD-' . date('Ymd-His') . '-' . random_int(100, 999);
        $orderStatus = 'En attente';

        $addressJson = json_encode($currentAddress, JSON_UNESCAPED_UNICODE);
        $linesJson = json_encode($orderLines, JSON_UNESCAPED_UNICODE);

        if ($addressJson === false || $linesJson === false) {
            $_SESSION['shipping_message'] = 'Erreur lors de la préparation de la commande.';
            header('Location: panier.php');
            exit;
        }

        try {
            $pdo->beginTransaction();

            $insertStmt = $pdo->prepare("
                INSERT INTO commandes (
                    utilisateur_id,
                    numero_commande,
                    statut,
                    adresse_livraison,
                    lignes_json,
                    total
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");

            $insertStmt->execute([
                $userId,
                $orderNumber,
                $orderStatus,
                $addressJson,
                $linesJson,
                $orderTotal
            ]);

            $pdo->commit();

            unset($_SESSION['cart'], $_SESSION['temp_delivery_address'], $_SESSION['selected_delivery_address']);
            $_SESSION['cart'] = [];

            header('Location: commandes.php?ordered=1');
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $_SESSION['shipping_message'] = 'Une erreur est survenue lors de l’enregistrement de votre commande.';
            header('Location: panier.php');
            exit;
        }
    }
}

if (isset($_SESSION['shipping_message'])) {
    $shippingMessage = (string) $_SESSION['shipping_message'];
    unset($_SESSION['shipping_message']);
}

$savedAddresses = extractSavedAddresses($user);
$activeAddress = null;

if ($selectedAddressKey === 'temp' && isset($_SESSION['temp_delivery_address']) && is_array($_SESSION['temp_delivery_address'])) {
    $activeAddress = $_SESSION['temp_delivery_address'];
}

if ($activeAddress === null) {
    foreach ($savedAddresses as $address) {
        if ($address['key'] === $selectedAddressKey) {
            $activeAddress = $address;
            break;
        }
    }
}

if ($activeAddress === null && !empty($savedAddresses)) {
    $activeAddress = $savedAddresses[0];
    $_SESSION['selected_delivery_address'] = $activeAddress['key'];
}

$cartItems = $_SESSION['cart'];
$productIds = [];
$boosterIds = [];

foreach ($cartItems as $item) {
    $productId = (int) ($item['product_id'] ?? 0);
    if ($productId > 0) {
        $productIds[] = $productId;
    }

    $boosters = $item['boosters'] ?? [];
    if (is_array($boosters)) {
        foreach ($boosters as $boosterId => $qty) {
            if ((int) $boosterId > 0 && (int) $qty > 0) {
                $boosterIds[] = (int) $boosterId;
            }
        }
    }
}

$productMap = [];
if (!empty($productIds)) {
    $uniqueProductIds = array_values(array_unique($productIds));
    $placeholders = implode(',', array_fill(0, count($uniqueProductIds), '?'));
    $stmt = $pdo->prepare("SELECT id, nom, prix_regulier, prix_promo, image_principale FROM produits WHERE id IN ($placeholders)");
    $stmt->execute($uniqueProductIds);

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $productMap[(int) $row['id']] = $row;
    }
}

$boosterMap = [];
if (!empty($boosterIds)) {
    $uniqueBoosterIds = array_values(array_unique($boosterIds));
    $placeholders = implode(',', array_fill(0, count($uniqueBoosterIds), '?'));
    $stmt = $pdo->prepare("SELECT id, nom, prix_regulier, prix_promo, image_principale FROM produits WHERE id IN ($placeholders)");
    $stmt->execute($uniqueBoosterIds);

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $boosterMap[(int) $row['id']] = $row;
    }
}

$total = 0.0;
include 'header.php';
?>

<style>
    @media (max-width: 768px) {
        .cart-page-title {
            text-align: center;
        }

        .cart-checkout-grid {
            display: flex !important;
            flex-direction: column;
            gap: 14px !important;
        }

        .cart-summary-block {
            order: 1;
        }

        .cart-delivery-block {
            order: 2;
        }

        .delivery-checkbox-label {
            align-items: center !important;
        }

        .delivery-checkbox-label input[type="checkbox"] {
            margin-top: 0 !important;
        }

        .summary-total-amount {
            font-size: 1.85rem;
        }
    }
</style>

<main style="max-width:1200px;margin:34px auto;padding:0 16px 60px;">
    <h1 class="cart-page-title" style="margin:0 0 20px;text-transform:uppercase;font-weight:900;">Finaliser ma commande</h1>

    <div class="cart-checkout-grid" style="display:grid;grid-template-columns:1.1fr 1fr;gap:20px;align-items:start;">
        <section class="cart-delivery-block" style="border:1px solid #eee;padding:20px;border-radius:20px;box-shadow:0 4px 12px rgba(0,0,0,0.03);background:#fff;">
            <div style="display:flex;justify-content:space-between;align-items:center;border-bottom:3px solid #ffcc00;margin-bottom:15px;padding-bottom:5px;">
                <h2 style="font-size:1.1rem;margin:0;text-transform:uppercase;">Infos client & livraison</h2>
                <?php if ($user): ?>
                    <button type="button" id="toggleContactEdit" style="background:none;border:none;cursor:pointer;font-size:1.1rem;" aria-label="Modifier mes coordonnées">
                        <i class="fas fa-pen"></i>
                    </button>
                <?php endif; ?>
            </div>

            <?php if ($user): ?>
                <p style="margin:8px 0;"><i class="fas fa-user" style="width:20px;color:#ffcc00;"></i> <strong>Pseudo :</strong> <?= e((string) ($user['pseudo'] ?? '')); ?></p>
                <p style="margin:8px 0;"><i class="fas fa-phone" style="width:20px;color:#ffcc00;"></i> <strong>Tél :</strong> <?= e((string) ($user['telephone'] ?? 'Non renseigné')); ?></p>
                <form method="post" action="panier.php" id="contactEditForm" style="display:none;margin:8px 0 12px;padding:12px;border:1px solid #eee;border-radius:12px;background:#fffdf0;">
                    <input type="hidden" name="action" value="update_contact">
                    <input type="text" name="telephone" value="<?= e((string) ($user['telephone'] ?? '')); ?>" placeholder="Téléphone" style="width:100%;padding:10px;border-radius:10px;border:1px solid #ddd;margin-bottom:8px;">
                    <button type="submit" style="width:100%;background:#ffcc00;border:none;padding:10px;border-radius:24px;font-weight:700;cursor:pointer;">Enregistrer mes coordonnées</button>
                </form>
            <?php else: ?>
                <div style="margin:8px 0 12px;padding:12px;border:1px solid #fecaca;border-radius:12px;background:#fff7ed;">
                    <p style="margin:0 0 8px;color:#b91c1c;"><strong>Connectez-vous</strong> pour lier l'adresse à votre profil et finaliser plus vite vos prochaines commandes.</p>
                    <div style="display:flex;flex-wrap:wrap;gap:8px;">
                        <a href="compte.php" style="display:inline-flex;align-items:center;justify-content:center;padding:10px 16px;background:#ffcc00;color:#111827;border-radius:999px;font-weight:800;text-decoration:none;">Se connecter</a>
                        <a href="compte.php#form-inscription" style="display:inline-flex;align-items:center;justify-content:center;padding:10px 16px;background:#ffffff;color:#111827;border:1px solid #d1d5db;border-radius:999px;font-weight:700;text-decoration:none;">Créer un compte</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($shippingMessage !== ''): ?>
                <p style="padding:10px 12px;background:#ecfdf5;border:1px solid #10b981;border-radius:8px;"><?= e($shippingMessage); ?></p>
            <?php endif; ?>

            <?php if (count($savedAddresses) >= 2): ?>
                <form method="post" action="panier.php" style="margin:15px 0;">
                    <input type="hidden" name="action" value="choose_delivery_address">
                    <p style="font-weight:700;margin-bottom:10px;">Choisissez une adresse de livraison :</p>
                    <?php foreach ($savedAddresses as $address): ?>
                        <label style="display:block;background:#fdfdfd;border:1px solid #f5f5f5;padding:12px;border-radius:12px;margin-bottom:10px;cursor:pointer;">
                            <input type="radio" name="delivery_address" value="<?= e($address['key']); ?>" <?= $activeAddress && $activeAddress['key'] === $address['key'] ? 'checked' : ''; ?>>
                            <strong style="color:#f0b400;"><?= e($address['label']); ?></strong><br>
                            <?= e($address['line']); ?><br><?= e($address['quartier']); ?>, <?= e($address['ville']); ?>
                        </label>
                    <?php endforeach; ?>
                    <button type="submit" style="width:100%;background:#ffcc00;border:none;padding:12px;border-radius:30px;font-weight:bold;cursor:pointer;">Utiliser cette adresse</button>
                </form>
            <?php elseif (!empty($savedAddresses)): ?>
                <?php $onlyAddress = $savedAddresses[0]; ?>
                <div style="margin-top:15px;background:#fdfdfd;padding:15px;border-radius:15px;border:1px solid #f5f5f5;">
                    <span style="font-weight:bold;color:#ffcc00;"><?= e($onlyAddress['label']); ?></span>
                    <p style="margin:6px 0 0;line-height:1.4;"><?= e($onlyAddress['line']); ?><br><?= e($onlyAddress['quartier']); ?>, <?= e($onlyAddress['ville']); ?></p>
                </div>
            <?php else: ?>
                <form method="post" action="panier.php" style="margin-top:15px;background:#fff;padding:15px;border:1px solid #eee;border-radius:15px;">
                    <input type="hidden" name="action" value="save_delivery_address">
                    <p style="font-weight:700;margin:0 0 12px;">Aucune adresse enregistrée : ajoutez votre adresse de livraison.</p>
                    <input type="text" name="line" placeholder="Adresse (Lot...)" required style="width:100%;padding:12px;margin-bottom:10px;border-radius:10px;border:1px solid #ddd;">
                    <div style="display:flex;gap:10px;">
                        <input type="text" name="quartier" placeholder="Quartier" style="flex:1;padding:12px;border-radius:10px;border:1px solid #ddd;">
                        <input type="text" name="ville" placeholder="Ville" style="flex:1;padding:12px;border-radius:10px;border:1px solid #ddd;">
                    </div>
                    <label class="delivery-checkbox-label" style="display:flex;align-items:center;gap:8px;margin-top:12px;font-size:0.95rem;">
                        <input type="checkbox" name="save_for_future" value="1">
                        Utiliser cette adresse pour mes prochaines commandes.
                    </label>
                    <button type="submit" style="width:100%;background:#ffcc00;border:none;padding:12px;border-radius:30px;margin-top:10px;font-weight:bold;cursor:pointer;">Valider l'adresse</button>
                </form>
            <?php endif; ?>
        </section>

        <section class="cart-summary-block" style="background:linear-gradient(160deg,#10131a 0%,#1c2433 100%);color:#fff;border-radius:20px;padding:20px;border:1px solid rgba(255,204,0,0.35);box-shadow:0 10px 24px rgba(0,0,0,0.24);">
            <h2 style="margin:0 0 14px;text-transform:uppercase;color:#ffcc00;">Récapitulatif panier</h2>

            <?php if (isset($_GET['added'])): ?>
                <p style="padding:10px 12px;background:rgba(16,185,129,0.2);border:1px solid #10b981;border-radius:8px;">Produit ajouté au panier.</p>
            <?php endif; ?>
            <?php if (isset($_GET['removed'])): ?>
                <p style="padding:10px 12px;background:rgba(239,68,68,0.2);border:1px solid #ef4444;border-radius:8px;">Produit retiré du panier.</p>
            <?php endif; ?>

            <?php if (empty($cartItems)): ?>
                <p>Votre panier est vide.</p>
            <?php else: ?>
                <?php foreach ($cartItems as $itemKey => $item): ?>
                    <?php
                    $productId = (int) ($item['product_id'] ?? 0);
                    $qty = max(1, (int) ($item['qty'] ?? 1));
                    $product = $productMap[$productId] ?? null;
                    if (!$product) {
                        continue;
                    }

                    $price = isset($product['prix_promo']) && $product['prix_promo'] !== null
                        ? (float) $product['prix_promo']
                        : (float) $product['prix_regulier'];
                    $variantLabel = '';
                    if (isset($item['variant']) && is_array($item['variant'])) {
                        $variantLabel = trim((string) ($item['variant']['label'] ?? ''));
                        if (isset($item['variant']['prix_promo']) && $item['variant']['prix_promo'] !== null) {
                            $price = (float) $item['variant']['prix_promo'];
                        } elseif (isset($item['variant']['prix_regulier'])) {
                            $price = (float) $item['variant']['prix_regulier'];
                        }
                    }
                    $lineTotal = $price * $qty;
                    $total += $lineTotal;
                    $productImage = buildProductImagePath(isset($product['image_principale']) ? (string) $product['image_principale'] : null);
                    ?>
                    <article style="border:1px solid rgba(255,255,255,0.13);border-radius:12px;padding:14px 14px;margin-bottom:12px;background:rgba(255,255,255,0.03);">
                        <div style="display:flex;gap:10px;align-items:center;margin-bottom:6px;">
                            <img src="<?= e($productImage); ?>" alt="<?= e($product['nom']); ?>" style="width:46px;height:46px;object-fit:cover;border-radius:10px;border:1px solid rgba(255,255,255,0.18);">
                            <h3 style="margin:0;font-size:1.05rem;font-weight:900;"><?= e($product['nom']); ?></h3>
                        </div>
                        <?php if ($variantLabel !== ''): ?>
                            <p style="margin:0 0 4px;">Variante : <?= e($variantLabel); ?></p>
                        <?php endif; ?>
                        <p style="margin:0 0 4px;">Quantité : <?= $qty; ?></p>
                        <p style="margin:0 0 8px;">Sous-total : <strong><?= number_format($lineTotal, 0, '.', ' '); ?> Ar</strong></p>

                        <?php if (!empty($item['boosters']) && is_array($item['boosters'])): ?>
                            <div style="margin:10px 0 10px;">
                                <div style="font-weight:800;margin-bottom:6px;">Boosters</div>
                                <ul style="margin:0;padding:0;list-style:none;display:flex;flex-direction:column;gap:6px;">
                                    <?php foreach ($item['boosters'] as $boosterId => $boosterQty): ?>
                                        <?php
                                        $boosterIdInt = (int) $boosterId;
                                        $boosterQtyInt = (int) $boosterQty;
                                        if ($boosterQtyInt <= 0 || !isset($boosterMap[$boosterIdInt])) {
                                            continue;
                                        }
                                        $booster = $boosterMap[$boosterIdInt];
                                        $boosterPrice = isset($booster['prix_promo']) && $booster['prix_promo'] !== null
                                            ? (float) $booster['prix_promo']
                                            : (float) $booster['prix_regulier'];
                                        $boosterLine = $boosterPrice * $boosterQtyInt;
                                        $total += $boosterLine;
                                        ?>
                                        <li style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;">
                                            <span style="display:block;min-width:0;flex:1;font-size:0.95rem;font-weight:700;line-height:1.3;word-break:break-word;"><?= e($booster['nom']); ?> x <?= $boosterQtyInt; ?> (<?= number_format($boosterLine, 0, '.', ' '); ?> Ar)</span>
                                            <form method="post" action="panier.php" style="margin:0;">
                                                <input type="hidden" name="action" value="remove_booster">
                                                <input type="hidden" name="item_key" value="<?= e((string) $itemKey); ?>">
                                                <input type="hidden" name="booster_id" value="<?= $boosterIdInt; ?>">
                                                <button type="submit" aria-label="Retirer ce booster" style="width:20px;height:20px;border:none;border-radius:50%;background:#fff;color:#111;font-size:11px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:1;">✕</button>
                                            </form>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="panier.php">
                            <input type="hidden" name="action" value="remove_item">
                            <input type="hidden" name="item_key" value="<?= e((string) $itemKey); ?>">
                            <button type="submit" style="background:#fff;color:#111;border:0;padding:8px 10px;border-radius:8px;cursor:pointer;font-weight:700;">Retirer</button>
                        </form>
                    </article>
                <?php endforeach; ?>

                <div style="display:flex;justify-content:space-between;align-items:center;border-top:1px solid rgba(255,204,0,0.45);padding-top:14px;margin-top:10px;">
                    <span style="font-weight:700;">Total</span>
                    <span class="summary-total-amount" style="font-size:clamp(1.15rem,5vw,1.9rem);font-weight:900;color:#ffcc00;text-align:right;"><?= number_format($total, 0, '.', ' '); ?> Ar</span>
                </div>
                <p style="margin:8px 0 0;font-size:0.9rem;opacity:0.9;">* Les frais de livraison seront communiqués ultérieurement.</p>
            <?php endif; ?>
        </section>
    </div>

    <form method="post" action="panier.php" style="margin-top:16px;">
        <input type="hidden" name="action" value="place_order">
        <button type="submit" style="width:100%;background:#ffcc00;color:#111;border:none;padding:14px 16px;border-radius:12px;font-weight:900;text-transform:uppercase;cursor:pointer;">
            Valider la commande
        </button>
    </form>
</main>

<script>
    (function () {
        const toggleBtn = document.getElementById('toggleContactEdit');
        const form = document.getElementById('contactEditForm');
        if (!toggleBtn || !form) {
            return;
        }

        toggleBtn.addEventListener('click', function () {
            const isHidden = form.style.display === 'none';
            form.style.display = isHidden ? 'block' : 'none';
        });
    })();
</script>

<?php include 'footer.php'; ?>
