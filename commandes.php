<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: compte.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

/**
 * @param mixed $value
 */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * @param string|null $json
 * @return array<mixed>
 */
function decodeJsonArray(?string $json): array
{
    if ($json === null || trim($json) === '') {
        return [];
    }

    $decoded = json_decode($json, true);
    return is_array($decoded) ? $decoded : [];
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

function getStatusBadgeStyle(string $status): string
{
    switch ($status) {
        case 'Livrée':
            return 'background:#dcfce7;color:#166534;border:1px solid #22c55e;'; // vert

        case 'Expédiée':
            return 'background:#fff7ed;color:#c2410c;border:1px solid #f97316;'; // orange

        case 'En attente':
            return 'background:#fee2e2;color:#991b1b;border:1px solid #ef4444;'; // rouge

        default:
            return 'background:#f1f5f9;color:#334155;border:1px solid #cbd5f5;'; // gris fallback
    }
}

$stmt = $pdo->prepare('
    SELECT
        id,
        numero_commande,
        statut,
        adresse_livraison,
        lignes_json,
        total,
        created_at,
        updated_at
    FROM commandes
    WHERE utilisateur_id = ?
    ORDER BY created_at DESC, id DESC
');
$stmt->execute([$userId]);
$rawOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$orders = [];
$productNames = [];

foreach ($rawOrders as $row) {
    $lines = decodeJsonArray(isset($row['lignes_json']) ? (string) $row['lignes_json'] : null);

    foreach ($lines as $line) {
        $name = trim((string) ($line['name'] ?? ''));
        if ($name !== '') {
            $productNames[] = $name;
        }
    }

    $orders[] = [
        'id' => (int) ($row['id'] ?? 0),
        'order_number' => (string) ($row['numero_commande'] ?? 'Commande'),
        'status' => (string) ($row['statut'] ?? 'En attente'),
        'address' => decodeJsonArray(isset($row['adresse_livraison']) ? (string) $row['adresse_livraison'] : null),
        'lines' => $lines,
        'total' => (float) ($row['total'] ?? 0),
        'created_at' => !empty($row['created_at']) ? date('d/m/Y H:i', strtotime((string) $row['created_at'])) : '',
        'updated_at' => !empty($row['updated_at']) ? date('d/m/Y H:i', strtotime((string) $row['updated_at'])) : '',
    ];
}

$productImageMap = [];
$productNames = array_values(array_unique(array_filter($productNames)));

if (!empty($productNames)) {
    $placeholders = implode(',', array_fill(0, count($productNames), '?'));
    $productStmt = $pdo->prepare("
        SELECT nom, image_principale
        FROM produits
        WHERE nom IN ($placeholders)
    ");
    $productStmt->execute($productNames);

    foreach ($productStmt->fetchAll(PDO::FETCH_ASSOC) as $productRow) {
        $productImageMap[(string) $productRow['nom']] = buildProductImagePath(
            isset($productRow['image_principale']) ? (string) $productRow['image_principale'] : null
        );
    }
}

include 'header.php';
?>

<style>
    .orders-page {
        max-width: 1050px;
        margin: 34px auto;
        padding: 0 16px 60px;
    }

    .orders-title {
        margin: 0 0 22px;
        text-transform: uppercase;
        font-weight: 900;
        letter-spacing: 0.3px;
    }

    .order-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
        margin-bottom: 18px;
        overflow: hidden;
    }

    .order-main {
        padding: 18px 18px 16px;
    }

    .order-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 10px;
    }

    .order-number {
        margin: 0;
        font-size: 1.15rem;
        font-weight: 900;
        color: #0f172a;
    }

    .order-badge {
        display: inline-flex;
        align-items: center;
        padding: 8px 12px;
        border-radius: 999px;
        font-size: 0.9rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .order-meta {
        margin: 0;
        color: #64748b;
        font-size: 0.98rem;
        line-height: 1.5;
    }

    .order-summary-line {
        margin-top: 14px;
        display: flex;
        flex-wrap: wrap;
        gap: 10px 16px;
        align-items: center;
        color: #334155;
        font-size: 0.96rem;
    }

    .order-summary-line strong {
        color: #0f172a;
    }

    .order-toggle {
        border-top: 1px solid #eef2f7;
        background: #f8fafc;
    }

    .order-toggle summary {
        list-style: none;
        cursor: pointer;
        padding: 14px 18px;
        font-weight: 800;
        color: #0f172a;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .order-toggle summary::-webkit-details-marker {
        display: none;
    }

    .order-toggle summary::after {
        content: "▾";
        font-size: 1rem;
        color: #475569;
        transition: transform 0.2s ease;
    }

    .order-toggle[open] summary::after {
        transform: rotate(180deg);
    }

    .order-details {
        padding: 0 18px 18px;
        background: #f8fafc;
    }

    .delivery-box {
        margin-top: 4px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 14px;
    }

    .delivery-title {
        margin: 0 0 8px;
        font-weight: 900;
        color: #0f172a;
    }

    .delivery-box p {
        margin: 4px 0;
        color: #334155;
    }

    .order-products {
        margin-top: 14px;
        display: grid;
        gap: 12px;
    }

    .order-product {
        display: grid;
        grid-template-columns: 70px 1fr;
        gap: 12px;
        align-items: start;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 12px;
    }

    .order-product-image {
        width: 70px;
        height: 70px;
        border-radius: 12px;
        object-fit: cover;
        background: #fff;
        border: 1px solid #e5e7eb;
    }

    .order-product-name {
        margin: 0 0 6px;
        font-size: 1rem;
        font-weight: 900;
        color: #0f172a;
    }

    .order-product-meta {
        margin: 0;
        color: #475569;
        line-height: 1.45;
    }

    .booster-list {
        margin: 8px 0 0;
        padding-left: 18px;
        color: #475569;
    }

    .order-total {
        margin-top: 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding-top: 14px;
        border-top: 1px solid #dbe3ee;
        font-size: 1rem;
        font-weight: 900;
        color: #0f172a;
    }

    .empty-orders {
        padding: 18px;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #fff;
    }

    @media (max-width: 768px) {
        .orders-page {
            margin: 24px auto;
            padding: 0 12px 40px;
        }

        .orders-title {
            text-align: center;
            font-size: 1.9rem;
        }

        .order-main,
        .order-details,
        .order-toggle summary {
            padding-left: 14px;
            padding-right: 14px;
        }

        .order-product {
            grid-template-columns: 56px 1fr;
            gap: 10px;
        }

        .order-product-image {
            width: 56px;
            height: 56px;
        }

        .order-total {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>

<main class="orders-page">
    <h1 class="orders-title">Mes commandes</h1>

    <?php if (empty($orders)): ?>
        <div class="empty-orders">
            Aucune commande pour le moment.
        </div>
    <?php else: ?>
        <?php foreach ($orders as $index => $order): ?>
            <?php
            $lineCount = is_array($order['lines']) ? count($order['lines']) : 0;
            $firstLine = $order['lines'][0] ?? null;
            $firstProductName = (string) ($firstLine['name'] ?? 'Produit');
            ?>
            <section class="order-card">
                <div class="order-main">
                    <div class="order-top">
                        <div>
                            <h2 class="order-number"><?= e($order['order_number']); ?></h2>
                            <p class="order-meta">Passée le <?= e($order['created_at']); ?></p>
                            <?php if (!empty($order['updated_at'])): ?>
                                <p class="order-meta">Dernière mise à jour : <?= e($order['updated_at']); ?></p>
                            <?php endif; ?>
                        </div>

                        <span class="order-badge" style="<?= e(getStatusBadgeStyle($order['status'])); ?>">
                            <?= e($order['status']); ?>
                        </span>
                    </div>

                    <div class="order-summary-line">
                        <span><strong>Produit principal :</strong> <?= e($firstProductName); ?></span>
                        <span><strong>Articles :</strong> <?= (int) $lineCount; ?></span>
                        <span><strong>Total :</strong> <?= number_format((float) $order['total'], 0, '.', ' '); ?> Ar</span>
                    </div>
                </div>

                <details class="order-toggle" <?= $index === 0 ? 'open' : ''; ?>>
                    <summary>Voir le détail de la commande</summary>

                    <div class="order-details">
                        <?php if (!empty($order['address'])): ?>
                            <div class="delivery-box">
                                <p class="delivery-title">Adresse de livraison</p>

                                <?php if (!empty($order['address']['label'])): ?>
                                    <p><?= e($order['address']['label']); ?></p>
                                <?php endif; ?>

                                <?php if (!empty($order['address']['line'])): ?>
                                    <p><?= e($order['address']['line']); ?></p>
                                <?php endif; ?>

                                <p>
                                    <?= e($order['address']['quartier'] ?? ''); ?>
                                    <?= !empty($order['address']['quartier']) && !empty($order['address']['ville']) ? ', ' : ''; ?>
                                    <?= e($order['address']['ville'] ?? ''); ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <div class="order-products">
                            <?php foreach (($order['lines'] ?? []) as $line): ?>
                                <?php
                                $lineName = (string) ($line['name'] ?? 'Produit');
                                $lineImage = $productImageMap[$lineName] ?? 'img/produits/placeholder.jpg';
                                ?>
                                <article class="order-product">
                                    <img
                                        src="<?= e($lineImage); ?>"
                                        alt="<?= e($lineName); ?>"
                                        class="order-product-image"
                                    >

                                    <div>
                                        <h3 class="order-product-name"><?= e($lineName); ?></h3>
                                        <p class="order-product-meta">
                                            Quantité : <?= (int) ($line['qty'] ?? 1); ?><br>
                                            Sous-total : <?= number_format((float) ($line['line_total'] ?? 0), 0, '.', ' '); ?> Ar
                                        </p>

                                        <?php if (!empty($line['boosters']) && is_array($line['boosters'])): ?>
                                            <ul class="booster-list">
                                                <?php foreach ($line['boosters'] as $booster): ?>
                                                    <li>
                                                        Booster <?= e($booster['name'] ?? 'Booster'); ?>
                                                        x <?= (int) ($booster['qty'] ?? 1); ?>
                                                        — <?= number_format((float) ($booster['line_total'] ?? 0), 0, '.', ' '); ?> Ar
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <div class="order-total">
                            <span>Total de la commande</span>
                            <span><?= number_format((float) ($order['total'] ?? 0), 0, '.', ' '); ?> Ar</span>
                        </div>
                    </div>
                </details>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
</main>

<?php include 'footer.php'; ?>
