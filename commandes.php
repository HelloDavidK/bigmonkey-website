<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: compte.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$orders = [];
if (isset($_SESSION['orders'][$userId]) && is_array($_SESSION['orders'][$userId])) {
    $orders = $_SESSION['orders'][$userId];
}

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if ($method === 'POST') {
    $action = trim((string) ($_POST['action'] ?? ''));

    if ($action === 'update_order_status') {
        $orderIndex = isset($_POST['order_index']) ? (int) $_POST['order_index'] : -1;
        $nextStatus = trim((string) ($_POST['next_status'] ?? ''));
        $allowedStatuses = ['En attente', 'Expédiée', 'Livrée'];

        if (
            $orderIndex >= 0
            && isset($_SESSION['orders'][$userId][$orderIndex])
            && in_array($nextStatus, $allowedStatuses, true)
        ) {
            $_SESSION['orders'][$userId][$orderIndex]['status'] = $nextStatus;
            $_SESSION['orders'][$userId][$orderIndex]['updated_at'] = date('d/m/Y H:i');
            header('Location: commandes.php?updated=1');
            exit;
        }
    }
}

$orders = [];
if (isset($_SESSION['orders'][$userId]) && is_array($_SESSION['orders'][$userId])) {
    $orders = $_SESSION['orders'][$userId];
}


/**
 * @param mixed $value
 */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

include 'header.php';
?>

<main style="max-width:980px;margin:30px auto;padding:0 16px 60px;">
    <h1 style="margin:0 0 18px;text-transform:uppercase;font-weight:900;">Mes commandes</h1>

     <?php if (isset($_GET['ordered'])): ?>
        <p style="padding:10px 12px;background:#ecfdf5;border:1px solid #10b981;border-radius:8px;">Commande validée avec succès.</p>
    <?php endif; ?>
    <?php if (isset($_GET['updated'])): ?>
        <p style="padding:10px 12px;background:#eff6ff;border:1px solid #3b82f6;border-radius:8px;">Statut de la commande mis à jour.</p>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
        <p style="padding:16px;border:1px solid #e5e7eb;border-radius:12px;">Aucune commande pour le moment.</p>
    <?php else: ?>
        <?php foreach ($orders as $orderIndex => $order): ?>
            <section style="border:1px solid #e5e7eb;border-radius:14px;padding:16px;margin-bottom:14px;background:#fff;">
                <div style="display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap;">
                    <strong><?= e($order['order_number'] ?? 'Commande'); ?></strong>
                    <span><?= e($order['status'] ?? 'En attente'); ?></span>
                </div>
                <p style="margin:6px 0;color:#64748b;">Passée le <?= e($order['created_at'] ?? ''); ?></p>
                <?php if (!empty($order['updated_at'])): ?>
                    <p style="margin:4px 0;color:#64748b;">Dernière mise à jour : <?= e($order['updated_at']); ?></p>
                <?php endif; ?>

                <ul style="margin:8px 0 0;padding-left:18px;">
                    <?php foreach (($order['lines'] ?? []) as $line): ?>
                        <li style="margin-bottom:7px;">
                            <strong><?= e($line['name'] ?? 'Produit'); ?></strong> x <?= (int) ($line['qty'] ?? 1); ?>
                            — <?= number_format((float) ($line['line_total'] ?? 0), 0, '.', ' '); ?> Ar
                            <?php if (!empty($line['boosters'])): ?>
                                <ul style="margin-top:4px;">
                                    <?php foreach ($line['boosters'] as $booster): ?>
                                        <li>Booster <?= e($booster['name'] ?? 'Booster'); ?> x <?= (int) ($booster['qty'] ?? 1); ?> — <?= number_format((float) ($booster['line_total'] ?? 0), 0, '.', ' '); ?> Ar</li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <p style="margin:8px 0 0;font-weight:900;">Total : <?= number_format((float) ($order['total'] ?? 0), 0, '.', ' '); ?> Ar</p>

                <form method="post" action="commandes.php" style="margin-top:12px;display:flex;flex-wrap:wrap;gap:8px;">
                    <input type="hidden" name="action" value="update_order_status">
                    <input type="hidden" name="order_index" value="<?= (int) $orderIndex; ?>">

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
                        <span style="font-size:0.9rem;color:#16a34a;font-weight:700;">Commande finalisée.</span>
                    <?php endif; ?>
                </form>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
</main>

<?php include 'footer.php'; ?>
