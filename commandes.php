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

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if ($method === 'POST') {
    $action = trim((string) ($_POST['action'] ?? ''));

    if ($action === 'update_order_status') {
        $orderId = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
        $nextStatus = trim((string) ($_POST['next_status'] ?? ''));
        $allowedStatuses = ['En attente', 'Expédiée', 'Livrée'];

        if ($orderId > 0 && in_array($nextStatus, $allowedStatuses, true)) {
            $checkStmt = $pdo->prepare('
                SELECT id, statut
                FROM commandes
                WHERE id = ? AND utilisateur_id = ?
                LIMIT 1
            ');
            $checkStmt->execute([$orderId, $userId]);
            $existingOrder = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($existingOrder) {
                $currentStatus = (string) ($existingOrder['statut'] ?? 'En attente');

                $isAllowedTransition =
                    ($currentStatus === 'En attente' && $nextStatus === 'Expédiée')
                    || ($currentStatus === 'Expédiée' && $nextStatus === 'Livrée')
                    || ($currentStatus === $nextStatus);

                if ($isAllowedTransition) {
                    $updateStmt = $pdo->prepare('
                        UPDATE commandes
                        SET statut = ?, updated_at = NOW()
                        WHERE id = ? AND utilisateur_id = ?
                    ');
                    $updateStmt->execute([$nextStatus, $orderId, $userId]);

                    header('Location: commandes.php?updated=1');
                    exit;
                }
            }
        }

        header('Location: commandes.php');
        exit;
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

foreach ($rawOrders as $row) {
    $orders[] = [
        'id' => (int) ($row['id'] ?? 0),
        'order_number' => (string) ($row['numero_commande'] ?? 'Commande'),
        'status' => (string) ($row['statut'] ?? 'En attente'),
        'address' => decodeJsonArray(isset($row['adresse_livraison']) ? (string) $row['adresse_livraison'] : null),
        'lines' => decodeJsonArray(isset($row['lignes_json']) ? (string) $row['lignes_json'] : null),
        'total' => (float) ($row['total'] ?? 0),
        'created_at' => !empty($row['created_at']) ? date('d/m/Y H:i', strtotime((string) $row['created_at'])) : '',
        'updated_at' => !empty($row['updated_at']) ? date('d/m/Y H:i', strtotime((string) $row['updated_at'])) : '',
    ];
}

include 'header.php';
?>

<main style="max-width:980px;margin:30px auto;padding:0 16px 60px;">
    <h1 style="margin:0 0 18px;text-transform:uppercase;font-weight:900;">Mes commandes</h1>

    <?php if (isset($_GET['ordered'])): ?>
        <p style="padding:10px 12px;background:#ecfdf5;border:1px solid #10b981;border-radius:8px;">
            Commande validée avec succès.
        </p>
    <?php endif; ?>

    <?php if (isset($_GET['updated'])): ?>
        <p style="padding:10px 12px;background:#eff6ff;border:1px solid #3b82f6;border-radius:8px;">
            Statut de la commande mis à jour.
        </p>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
        <p style="padding:16px;border:1px solid #e5e7eb;border-radius:12px;">
            Aucune commande pour le moment.
        </p>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <section style="border:1px solid #e5e7eb;border-radius:14px;padding:16px;margin-bottom:14px;background:#fff;">
                <div style="display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap;align-items:center;">
                    <strong><?= e($order['order_number']); ?></strong>
                    <span style="font-weight:700;<?= $order['status'] === 'Livrée' ? 'color:#16a34a;' : ($order['status'] === 'Expédiée' ? 'color:#2563eb;' : 'color:#92400e;'); ?>">
                        <?= e($order['status']); ?>
                    </span>
                </div>

                <p style="margin:6px 0;color:#64748b;">
                    Passée le <?= e($order['created_at']); ?>
                </p>

                <?php if (!empty($order['updated_at'])): ?>
                    <p style="margin:4px 0;color:#64748b;">
                        Dernière mise à jour : <?= e($order['updated_at']); ?>
                    </p>
                <?php endif; ?>

                <?php if (!empty($order['address'])): ?>
                    <div style="margin:12px 0 0;padding:12px;border-radius:10px;background:#f8fafc;border:1px solid #e2e8f0;">
                        <p style="margin:0 0 6px;font-weight:800;">Adresse de livraison</p>
                        <?php if (!empty($order['address']['label'])): ?>
                            <p style="margin:0 0 4px;"><?= e($order['address']['label']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($order['address']['line'])): ?>
                            <p style="margin:0;"><?= e($order['address']['line']); ?></p>
                        <?php endif; ?>
                        <p style="margin:4px 0 0;">
                            <?= e($order['address']['quartier'] ?? ''); ?>
                            <?= !empty($order['address']['quartier']) && !empty($order['address']['ville']) ? ', ' : ''; ?>
                            <?= e($order['address']['ville'] ?? ''); ?>
                        </p>
                    </div>
                <?php endif; ?>

                <ul style="margin:14px 0 0;padding-left:18px;">
                    <?php foreach (($order['lines'] ?? []) as $line): ?>
                        <li style="margin-bottom:7px;">
                            <strong><?= e($line['name'] ?? 'Produit'); ?></strong>
                            x <?= (int) ($line['qty'] ?? 1); ?>
                            — <?= number_format((float) ($line['line_total'] ?? 0), 0, '.', ' '); ?> Ar

                            <?php if (!empty($line['boosters']) && is_array($line['boosters'])): ?>
                                <ul style="margin-top:4px;">
                                    <?php foreach ($line['boosters'] as $booster): ?>
                                        <li>
                                            Booster <?= e($booster['name'] ?? 'Booster'); ?>
                                            x <?= (int) ($booster['qty'] ?? 1); ?>
                                            — <?= number_format((float) ($booster['line_total'] ?? 0), 0, '.', ' '); ?> Ar
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <p style="margin:10px 0 0;font-weight:900;">
                    Total : <?= number_format((float) ($order['total'] ?? 0), 0, '.', ' '); ?> Ar
                </p>

                <form method="post" action="commandes.php" style="margin-top:12px;display:flex;flex-wrap:wrap;gap:8px;">
                    <input type="hidden" name="action" value="update_order_status">
                    <input type="hidden" name="order_id" value="<?= (int) $order['id']; ?>">

                    <?php $status = (string) ($order['status'] ?? 'En attente'); ?>

                    <?php if ($status === 'En attente'): ?>
                        <input type="hidden" name="next_status" value="Expédiée">
                        <button type="submit" style="background:#2563eb;color:#fff;border:none;border-radius:999px;padding:8px 14px;cursor:pointer;font-weight:700;">
                            Marquer comme expédiée
                        </button>
                    <?php elseif ($status === 'Expédiée'): ?>
                        <input type="hidden" name="next_status" value="Livrée">
                        <button type="submit" style="background:#16a34a;color:#fff;border:none;border-radius:999px;padding:8px 14px;cursor:pointer;font-weight:700;">
                            Marquer comme livrée
                        </button>
                    <?php else: ?>
                        <span style="font-size:0.9rem;color:#16a34a;font-weight:700;">
                            Commande finalisée.
                        </span>
                    <?php endif; ?>
                </form>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
</main>

<?php include 'footer.php'; ?>
