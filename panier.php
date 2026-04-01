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

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if ($method === 'POST') {
    $action = trim((string) ($_POST['action'] ?? ''));

    if ($action === 'add_bundle') {
        $mainProductId = sanitizePositiveInt($_POST['main_product_id'] ?? 0, 0);
        $qtyMain = max(1, sanitizePositiveInt($_POST['qty_main'] ?? 1, 1));

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

        header('Location: panier.php?added=1');
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
    $stmt = $pdo->prepare("SELECT id, nom, prix_regulier, prix_promo FROM produits WHERE id IN ($placeholders)");
    $stmt->execute(array_values(array_unique($productIds)));

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $productMap[(int) $row['id']] = $row;
    }
}

$boosterMap = [];
if (!empty($boosterIds)) {
    $placeholders = implode(',', array_fill(0, count($boosterIds), '?'));
    $stmt = $pdo->prepare("SELECT id, nom, prix_regulier, prix_promo FROM produits WHERE id IN ($placeholders)");
    $stmt->execute(array_values(array_unique($boosterIds)));

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $boosterMap[(int) $row['id']] = $row;
    }
}

$total = 0.0;
include 'header.php';
?>

<main style="max-width:960px;margin:30px auto;padding:0 16px;">
    <h1>Mon panier</h1>

    <?php if (isset($_GET['added'])): ?>
        <p style="padding:10px 12px;background:#ecfdf5;border:1px solid #10b981;border-radius:8px;">Produit ajouté au panier.</p>
    <?php endif; ?>

    <?php if (isset($_GET['removed'])): ?>
        <p style="padding:10px 12px;background:#fef2f2;border:1px solid #ef4444;border-radius:8px;">Produit retiré du panier.</p>
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
            ?>
            <article style="border:1px solid #e5e7eb;border-radius:10px;padding:14px 16px;margin-bottom:12px;">
                <h2 style="margin:0 0 8px;"><?= e($product['nom']); ?></h2>
                <p style="margin:0 0 6px;">Quantité: <?= $qty; ?></p>
                <p style="margin:0 0 6px;">Sous-total: <?= number_format($lineTotal, 0, '.', ' '); ?> Ar</p>

                <?php if (!empty($item['boosters']) && is_array($item['boosters'])): ?>
                    <ul>
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
                            <li>
                                Booster <?= e($booster['nom']); ?> x <?= $boosterQtyInt; ?>
                                (<?= number_format($boosterLine, 0, '.', ' '); ?> Ar)
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <form method="post" action="panier.php" style="margin-top:10px;">
                    <input type="hidden" name="action" value="remove_item">
                    <input type="hidden" name="item_key" value="<?= e((string) $itemKey); ?>">
                    <button type="submit" style="background:#111;color:#fff;border:0;padding:8px 10px;border-radius:8px;cursor:pointer;">Retirer</button>
                </form>
            </article>
        <?php endforeach; ?>

        <p style="font-size:20px;font-weight:700;">Total: <?= number_format($total, 0, '.', ' '); ?> Ar</p>
    <?php endif; ?>
</main>

<?php include 'footer.php'; ?>