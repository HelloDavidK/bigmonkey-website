<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';
include 'header.php';

$slug = isset($_GET['slug']) ? trim((string) $_GET['slug']) : '';
$slug = preg_match('/^[a-zA-Z0-9_-]{1,120}$/', $slug) ? $slug : '';

/**
 * @param mixed $value
 */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$product = null;

if ($slug !== '') {
    $stmt = $pdo->prepare('SELECT * FROM produits WHERE slug = :slug AND is_active = 1 LIMIT 1');
    $stmt->execute(['slug' => $slug]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}
?>

<main style="max-width: 1200px; margin: 40px auto; padding: 0 20px;">
    <?php if (!$product): ?>
        <section style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:32px; text-align:center;">
            <h1 style="font-size:2rem; margin-bottom:10px;">Produit introuvable</h1>
            <p style="color:#6b7280; margin-bottom:20px;">Le produit demandé n'existe pas ou n'est pas actif.</p>
            <a href="collection.php" style="display:inline-block; background:#111; color:#fff; padding:10px 18px; border-radius:999px; text-decoration:none; font-weight:700;">Retour à la collection</a>
        </section>
    <?php else: ?>
        <?php
        $prixRegulier = (float) ($product['prix_regulier'] ?? 0);
        $prixPromo = isset($product['prix_promo']) && $product['prix_promo'] !== null ? (float) $product['prix_promo'] : null;
        $prixFinal = $prixPromo ?: $prixRegulier;
        ?>
        <section style="display:grid; grid-template-columns: minmax(260px, 420px) 1fr; gap:32px; background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:24px;">
            <div>
                <img
                    src="img/produits/<?= e($product['image_principale'] ?? 'placeholder.jpg'); ?>"
                    alt="<?= e($product['nom'] ?? 'Produit'); ?>"
                    style="width:100%; border-radius:10px; object-fit:cover;"
                >
            </div>

            <div>
                <p style="margin:0 0 6px; color:#ffb800; font-weight:700; text-transform:uppercase;"><?= e($product['marque'] ?? 'Big Monkey'); ?></p>
                <h1 style="margin:0 0 10px; font-size:2rem; line-height:1.2;"><?= e($product['nom'] ?? 'Produit'); ?></h1>

                <?php if (!empty($product['description'])): ?>
                    <p style="color:#4b5563; margin:0 0 18px; line-height:1.6;"><?= nl2br(e($product['description'])); ?></p>
                <?php endif; ?>

                <div style="margin-bottom:18px;">
                    <?php if ($prixPromo): ?>
                        <span style="text-decoration:line-through; color:#9ca3af; margin-right:8px;"><?= number_format($prixRegulier, 0, '.', ' '); ?> Ar</span>
                    <?php endif; ?>
                    <strong style="font-size:1.7rem; color:#111;"><?= number_format($prixFinal, 0, '.', ' '); ?> Ar</strong>
                </div>

                <div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:20px;">
                    <?php if (!empty($product['saveur'])): ?>
                        <span style="padding:6px 10px; border:1px solid #e5e7eb; border-radius:999px; font-size:.9rem;">Saveur: <?= e($product['saveur']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($product['contenance'])): ?>
                        <span style="padding:6px 10px; border:1px solid #e5e7eb; border-radius:999px; font-size:.9rem;"><?= e((string) $product['contenance']); ?> ML</span>
                    <?php endif; ?>
                    <?php if (!empty($product['ratio_pg_vg'])): ?>
                        <span style="padding:6px 10px; border:1px solid #e5e7eb; border-radius:999px; font-size:.9rem;">PG/VG: <?= e($product['ratio_pg_vg']); ?></span>
                    <?php endif; ?>
                </div>

                <a href="collection.php?cat=<?= urlencode((string) ($product['categorie_parent'] ?? 'tous')); ?>" style="display:inline-block; background:#111; color:#fff; padding:11px 18px; border-radius:999px; text-decoration:none; font-weight:700;">
                    Retour aux produits similaires
                </a>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php include 'footer.php'; ?>
