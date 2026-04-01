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

        if ($mainProductId > 0) {
            $bundleKey = 'main_' . $mainProductId;
            if (!isset($_SESSION['cart'][$bundleKey])) {
                $_SESSION['cart'][$bundleKey] = [
                    'product_id' => $mainProductId,
                    'qty' => 0,
                    'boosters' => [],
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
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $stmt = $pdo->prepare("SELECT id, nom, prix_regulier, prix_promo, image_principale FROM produits WHERE id IN ($placeholders)");
    $stmt->execute(array_values(array_unique($productIds)));

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $productMap[(int) $row['id']] = $row;
    }
}

$boosterMap = [];
if (!empty($boosterIds)) {
    $placeholders = implode(',', array_fill(0, count($boosterIds), '?'));
    $stmt = $pdo->prepare("SELECT id, nom, prix_regulier, prix_promo, image_principale FROM produits WHERE id IN ($placeholders)");
    $stmt->execute(array_values(array_unique($boosterIds)));

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
                <p style="margin:8px 0;color:#b91c1c;"><strong>Connectez-vous</strong> pour lier l'adresse à votre profil.</p>
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
                    $lineTotal = $price * $qty;
                    $total += $lineTotal;
                    $productImage = buildProductImagePath(isset($product['image_principale']) ? (string) $product['image_principale'] : null);
                    ?>
                    <article style="border:1px solid rgba(255,255,255,0.13);border-radius:12px;padding:14px 14px;margin-bottom:12px;background:rgba(255,255,255,0.03);">
                        <div style="display:flex;gap:10px;align-items:center;margin-bottom:6px;">
                            <img src="<?= e($productImage); ?>" alt="<?= e($product['nom']); ?>" style="width:46px;height:46px;object-fit:cover;border-radius:10px;border:1px solid rgba(255,255,255,0.18);">
                            <h3 style="margin:0;font-size:1.05rem;font-weight:900;"><?= e($product['nom']); ?></h3>
                        </div>
                        <p style="margin:0 0 4px;">Quantité : <?= $qty; ?></p>
                        <p style="margin:0 0 8px;">Sous-total : <strong><?= number_format($lineTotal, 0, '.', ' '); ?> Ar</strong></p>
                        <?php if (!empty($item['boosters']) && is_array($item['boosters'])): ?>
                            <ul style="margin:0 0 10px 17px;padding:0;">
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
                                    <li style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                                        <span style="display:block;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">Booster <strong><?= e($booster['nom']); ?></strong> x <?= $boosterQtyInt; ?> (<?= number_format($boosterLine, 0, '.', ' '); ?> Ar)</span>
                                        <form method="post" action="panier.php" style="margin:0;">
                                            <input type="hidden" name="action" value="remove_booster">
                                            <input type="hidden" name="item_key" value="<?= e((string) $itemKey); ?>">
                                            <input type="hidden" name="booster_id" value="<?= $boosterIdInt; ?>">
                                            <button type="submit" aria-label="Retirer ce booster" style="width:26px;height:26px;border:none;border-radius:50%;background:#fff;color:#111;font-weight:900;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:1;">✕</button>
                                        </form>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
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
                    <span style="font-size:1.3rem;font-weight:900;color:#ffcc00;"><?= number_format($total, 0, '.', ' '); ?> Ar</span>
                </div>
                <p style="margin:8px 0 0;font-size:0.9rem;opacity:0.9;">* Les frais de livraison seront communiqués ultérieurement.</p>
            <?php endif; ?>
        </section>
    </div>

    <div style="margin-top:16px;">
        <button type="button" style="width:100%;background:#ffcc00;color:#111;border:none;padding:14px 16px;border-radius:12px;font-weight:900;text-transform:uppercase;cursor:pointer;">
            Valider la commande
        </button>
    </div>
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
