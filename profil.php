<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header('Location: compte.php');
    exit();
}

require_once 'config.php';
$userId = (int) $_SESSION['user_id'];
$message_status = "";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// --- LOGIQUE DE TRAITEMENT (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrfToken, $postedToken)) {
        $message_status = "Session expirée, merci de recharger la page.";
    } else {
        try {
            // 1. Mise à jour Infos Personnelles
            if (isset($_POST['update_perso'])) {
                $pseudo = trim($_POST['pseudo'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $tel = trim($_POST['tel'] ?? '');

                if ($pseudo === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $message_status = "Pseudo / Email invalide.";
                } else {
                    $check = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ? AND id != ?");
                    $check->execute([$email, $userId]);
                    if ($check->fetch()) {
                        $message_status = "Cet email est déjà utilisé.";
                    } else {
                        $stmt = $pdo->prepare("UPDATE utilisateurs SET pseudo = ?, email = ?, telephone = ? WHERE id = ?");
                        $stmt->execute([$pseudo, $email, $tel, $userId]);
                        $_SESSION['pseudo'] = $pseudo;
                        $_SESSION['email'] = $email;
                        $message_status = "Profil mis à jour !";
                    }
                }
            }

            // 2. Gestion des Adresses (Sauvegarde / Ajout)
            if (isset($_POST['update_addr1'])) {
                $stmt = $pdo->prepare("UPDATE utilisateurs SET adresse_1 = ?, quartier_1 = ?, ville_1 = ? WHERE id = ?");
                $stmt->execute([
                    trim($_POST['line1'] ?? ''),
                    trim($_POST['quartier1'] ?? ''),
                    trim($_POST['ville1'] ?? ''),
                    $userId
                ]);
                $message_status = "Adresse 1 enregistrée !";
            }
            if (isset($_POST['add_addr2'])) {
                $stmt = $pdo->prepare("UPDATE utilisateurs SET adresse_2 = ?, quartier_2 = ?, ville_2 = ? WHERE id = ?");
                $stmt->execute([
                    trim($_POST['line2'] ?? ''),
                    trim($_POST['quartier2'] ?? ''),
                    trim($_POST['ville2'] ?? ''),
                    $userId
                ]);
                $message_status = "Adresse 2 enregistrée !";
            }

            // 3. LOGIQUE DE SUPPRESSION
            if (isset($_POST['delete_addr1'])) {
                $stmt = $pdo->prepare("UPDATE utilisateurs SET adresse_1 = NULL, quartier_1 = NULL, ville_1 = NULL WHERE id = ?");
                $stmt->execute([$userId]);
                $message_status = "Adresse 1 supprimée.";
            }
            if (isset($_POST['delete_addr2'])) {
                $stmt = $pdo->prepare("UPDATE utilisateurs SET adresse_2 = NULL, quartier_2 = NULL, ville_2 = NULL WHERE id = ?");
                $stmt->execute([$userId]);
                $message_status = "Adresse 2 supprimée.";
            }

            // 4. Gestion Mot de Passe
            if (isset($_POST['update_pwd'])) {
                $newPwd = $_POST['new_pwd'] ?? '';
                $confPwd = $_POST['conf_pwd'] ?? '';
                if ($newPwd === '' || strlen($newPwd) < 8) {
                    $message_status = "Le mot de passe doit contenir au moins 8 caractères.";
                } elseif ($newPwd !== $confPwd) {
                    $message_status = "Les deux mots de passe ne correspondent pas.";
                } else {
                    $hash = password_hash($newPwd, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?");
                    $stmt->execute([$hash, $userId]);
                    $message_status = "Mot de passe modifié !";
                }
            }
        } catch (PDOException $e) {
            error_log("Erreur Profil : " . $e->getMessage());
            $message_status = "Une erreur technique est survenue.";
        }
    }
}

// RÉCUPÉRATION DES DONNÉES UTILISATEUR
$query = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$query->execute([$userId]);
$user = $query->fetch();

if (!$user) {
    session_destroy();
    header('Location: compte.php');
    exit();
}

include 'header.php'; 
?>

<div class="profil-wrapper" style="background: #fff; color: #000; padding: 30px 15px 100px 15px; font-family: sans-serif; position: relative; z-index: 1;">
    <div class="container" style="max-width: 600px; margin: 0 auto;">
        
        <?php if($message_status): ?>
            <div style="background:#f0f9f1; color:#155724; padding:15px; border-radius:12px; margin-bottom:20px; text-align:center; border: 1px solid #c3e6cb; font-weight: bold;">
                <?= $message_status ?>
            </div>
        <?php endif; ?>

        <h1 style="text-align: center; text-transform: uppercase; font-weight: 900; margin-bottom: 30px; letter-spacing: 1px;">Mon Profil</h1>

        <form method="POST" style="border: 1px solid #eee; padding: 20px; border-radius: 20px; margin-bottom: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #ffcc00; margin-bottom: 15px; padding-bottom: 5px;">
                <h2 style="font-size: 1.1rem; margin: 0; text-transform: uppercase;">Mes Informations</h2>
                <button type="button" onclick="toggleSet('perso')" style="background:none; border:none; cursor:pointer; font-size: 1.2rem;"><i class="fas fa-edit"></i></button>
            </div>
            <div id="group-perso">
                <div class="view-mode">
                    <p style="margin: 8px 0;"><i class="fas fa-user" style="width:20px; color:#ffcc00;"></i> <strong>Pseudo :</strong> <?= htmlspecialchars($user['pseudo']) ?></p>
                    <p style="margin: 8px 0;"><i class="fas fa-envelope" style="width:20px; color:#ffcc00;"></i> <strong>Email :</strong> <?= htmlspecialchars($user['email']) ?></p>
                    <p style="margin: 8px 0;"><i class="fas fa-phone" style="width:20px; color:#ffcc00;"></i> <strong>Tél :</strong> <?= htmlspecialchars($user['telephone'] ?: 'Non renseigné') ?></p>
                </div>
                <div class="edit-mode" style="display:none; margin-top:15px;">
                    <input type="text" name="pseudo" value="<?= htmlspecialchars($user['pseudo']) ?>" style="width:100%; padding:12px; margin-bottom:10px; border-radius:10px; border:1px solid #ddd;">
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" style="width:100%; padding:12px; margin-bottom:10px; border-radius:10px; border:1px solid #ddd;">
                    <input type="text" name="tel" value="<?= htmlspecialchars($user['telephone']) ?>" style="width:100%; padding:12px; margin-bottom:15px; border-radius:10px; border:1px solid #ddd;">
                    <button type="submit" name="update_perso" style="width:100%; background:#ffcc00; color:#000; border:none; padding:14px; border-radius:30px; font-weight:bold; cursor:pointer;">Enregistrer</button>
                </div>
            </div>
        </form>

        <div style="border: 1px solid #eee; padding: 20px; border-radius: 20px; margin-bottom: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
            <h2 style="font-size: 1.1rem; border-bottom: 3px solid #ffcc00; margin-bottom: 20px; padding-bottom: 5px; text-transform: uppercase;">Adresses de livraison</h2>
            
            <?php if (!empty($user['adresse_1'])): ?>
            <div id="group-addr1" style="margin-bottom: 25px; background: #fdfdfd; padding: 15px; border-radius: 15px; border: 1px solid #f5f5f5;">
                <div style="display:flex; justify-content:space-between; align-items: center; margin-bottom: 10px;">
                    <span style="font-weight: bold; color: #ffcc00;">Adresse Principale</span>
                    <div style="display: flex; gap: 15px;">
                        <button type="button" onclick="toggleSet('addr1')" style="background:none; border:none; cursor:pointer; font-size: 1.1rem;"><i class="fas fa-edit"></i></button>
                        <form method="POST" id="form-delete-1">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            <input type="hidden" name="delete_addr1" value="1">
                            <button type="button" onclick="showConfirm(1)" style="background:none; border:none; cursor:pointer; color: #ff4444; font-size: 1.1rem;"><i class="fas fa-times-circle"></i></button>
                        </form>
                    </div>
                </div>
                <div class="view-mode">
                    <p style="margin:0; font-size: 0.95rem; line-height: 1.4;"><?= htmlspecialchars($user['adresse_1']) ?><br>
                    <?= htmlspecialchars($user['quartier_1']) ?>, <?= htmlspecialchars($user['ville_1']) ?></p>
                </div>
                <div class="edit-mode" style="display:none;">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="text" name="line1" value="<?= htmlspecialchars($user['adresse_1']) ?>" style="width:100%; padding:10px; margin-bottom:8px; border-radius:8px; border:1px solid #ddd;">
                        <div style="display:flex; gap:8px;">
                            <input type="text" name="quartier1" value="<?= htmlspecialchars($user['quartier_1']) ?>" placeholder="Quartier" style="flex:1; padding:10px; border-radius:8px; border:1px solid #ddd;">
                            <input type="text" name="ville1" value="<?= htmlspecialchars($user['ville_1']) ?>" placeholder="Ville" style="flex:1; padding:10px; border-radius:8px; border:1px solid #ddd;">
                        </div>
                        <button type="submit" name="update_addr1" style="width:100%; background:#ffcc00; border:none; padding:12px; border-radius:30px; margin-top:10px; font-weight: bold;">Sauvegarder</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($user['adresse_2'])): ?>
            <div id="group-addr2" style="margin-bottom: 20px; background: #fdfdfd; padding: 15px; border-radius: 15px; border: 1px solid #f5f5f5;">
                <div style="display:flex; justify-content:space-between; align-items: center; margin-bottom: 10px;">
                    <span style="font-weight: bold; color: #ffcc00;">Deuxième Adresse</span>
                    <div style="display: flex; gap: 15px;">
                        <button type="button" onclick="toggleSet('addr2')" style="background:none; border:none; cursor:pointer; font-size: 1.1rem;"><i class="fas fa-edit"></i></button>
                        <form method="POST" id="form-delete-2">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            <input type="hidden" name="delete_addr2" value="1">
                            <button type="button" onclick="showConfirm(2)" style="background:none; border:none; cursor:pointer; color: #ff4444; font-size: 1.1rem;"><i class="fas fa-times-circle"></i></button>
                        </form>
                    </div>
                </div>
                <div class="view-mode">
                    <p style="margin:0; font-size: 0.95rem; line-height: 1.4;"><?= htmlspecialchars($user['adresse_2']) ?><br>
                    <?= htmlspecialchars($user['quartier_2']) ?>, <?= htmlspecialchars($user['ville_2']) ?></p>
                </div>
                <div class="edit-mode" style="display:none;">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="text" name="line2" value="<?= htmlspecialchars($user['adresse_2']) ?>" style="width:100%; padding:10px; margin-bottom:8px; border-radius:8px; border:1px solid #ddd;">
                        <div style="display:flex; gap:8px;">
                            <input type="text" name="quartier2" value="<?= htmlspecialchars($user['quartier_2']) ?>" placeholder="Quartier" style="flex:1; padding:10px; border-radius:8px; border:1px solid #ddd;">
                            <input type="text" name="ville2" value="<?= htmlspecialchars($user['ville_2']) ?>" placeholder="Ville" style="flex:1; padding:10px; border-radius:8px; border:1px solid #ddd;">
                        </div>
                        <button type="submit" name="add_addr2" style="width:100%; background:#ffcc00; border:none; padding:12px; border-radius:30px; margin-top:10px; font-weight: bold;">Mettre à jour</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <?php if (empty($user['adresse_1']) || empty($user['adresse_2'])): ?>
                <button type="button" onclick="toggleSet('new-addr')" id="btn-add" style="width: 100%; border: 2px dashed #ffcc00; background: #fffdf0; padding: 18px; border-radius: 15px; cursor: pointer; font-weight: bold; color: #555; transition: 0.3s;">
                    <i class="fas fa-plus"></i> Ajouter une adresse de livraison
                </button>
                <div id="group-new-addr" style="display: none; margin-top: 15px; background: #fff; padding: 15px; border: 1px solid #eee; border-radius: 15px;">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <?php $target = empty($user['adresse_1']) ? 'line1' : 'line2'; ?>
                        <?php $btn_name = empty($user['adresse_1']) ? 'update_addr1' : 'add_addr2'; ?>
                        <input type="text" name="<?= $target ?>" placeholder="Adresse (ex: Lot IVG...)" required style="width:100%; padding:12px; margin-bottom:10px; border-radius:10px; border:1px solid #ddd;">
                        <div style="display:flex; gap:10px;">
                            <input type="text" name="<?= str_replace('line', 'quartier', $target) ?>" placeholder="Quartier" style="flex:1; padding:12px; border-radius:10px; border:1px solid #ddd;">
                            <input type="text" name="<?= str_replace('line', 'ville', $target) ?>" placeholder="Ville" style="flex:1; padding:12px; border-radius:10px; border:1px solid #ddd;">
                        </div>
                        <div style="display: flex; gap: 10px; margin-top: 15px;">
                            <button type="submit" name="<?= $btn_name ?>" style="flex: 2; background:#ffcc00; border:none; padding:12px; border-radius:30px; font-weight:bold; cursor:pointer;">Enregistrer</button>
                            <button type="button" onclick="toggleSet('new-addr')" style="flex: 1; background:#eee; border:none; padding:12px; border-radius:30px; cursor:pointer;">Annuler</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <form method="POST" style="border: 1px solid #eee; padding: 20px; border-radius: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #ffcc00; margin-bottom: 15px; padding-bottom: 5px;">
                <h2 style="font-size: 1.1rem; margin: 0; text-transform: uppercase;">Sécurité</h2>
                <button type="button" onclick="toggleSet('pwd')" style="background:none; border:none; cursor:pointer; font-size: 1.2rem;"><i class="fas fa-edit"></i></button>
            </div>
            <div id="group-pwd">
                <div class="view-mode">Mot de passe : <span style="letter-spacing: 3px;">**********</span></div>
                <div class="edit-mode" style="display:none; margin-top:15px;">
                    <input type="password" name="new_pwd" placeholder="Nouveau mot de passe" style="width:100%; padding:12px; margin-bottom:10px; border-radius:10px; border:1px solid #ddd;">
                    <input type="password" name="conf_pwd" placeholder="Confirmer le mot de passe" style="width:100%; padding:12px; margin-bottom:15px; border-radius:10px; border:1px solid #ddd;">
                    <button type="submit" name="update_pwd" style="width:100%; background:#000; color:#fff; border:none; padding:14px; border-radius:30px; font-weight:bold; cursor:pointer;">Changer le mot de passe</button>
                </div>
            </div>
        </form>

    </div>
</div>

<div id="custom-confirm" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:10000; align-items:center; justify-content:center; padding: 15px;">
    <div style="background:#fff; padding:30px; border-radius:20px; max-width:400px; width:100%; text-align:center; box-shadow:0 10px 25px rgba(0,0,0,0.3);">
        <i class="fas fa-exclamation-triangle" style="font-size:3rem; color:#ffcc00; margin-bottom:15px;"></i>
        <h3 style="margin-bottom:10px; text-transform:uppercase; font-weight:900;">Supprimer l'adresse ?</h3>
        <p style="color:#666; margin-bottom:25px; line-height: 1.5;">Voulez-vous vraiment retirer cette adresse de livraison ?</p>
        <div style="display:flex; gap:10px;">
            <button id="confirm-yes" style="flex:1; background:#ff4444; color:#fff; border:none; padding:12px; border-radius:30px; font-weight:bold; cursor:pointer;">Supprimer</button>
            <button id="confirm-no" style="flex:1; background:#eee; color:#000; border:none; padding:12px; border-radius:30px; font-weight:bold; cursor:pointer;">Annuler</button>
        </div>
    </div>
</div>

<script>
let currentDeleteForm = null;

function showConfirm(addrNumber) {
    currentDeleteForm = document.getElementById('form-delete-' + addrNumber);
    const modal = document.getElementById('custom-confirm');
    modal.style.display = 'flex';
}

document.getElementById('confirm-no').onclick = function() {
    document.getElementById('custom-confirm').style.display = 'none';
};

document.getElementById('confirm-yes').onclick = function() {
    if(currentDeleteForm) {
        currentDeleteForm.submit();
    }
};

window.onclick = function(event) {
    const modal = document.getElementById('custom-confirm');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}

function toggleSet(id) {
    const group = document.getElementById('group-' + id);
    if(!group) return;
    
    if(id === 'new-addr') {
        group.style.display = (group.style.display === 'none' || group.style.display === '') ? 'block' : 'none';
        document.getElementById('btn-add').style.display = (group.style.display === 'block') ? 'none' : 'block';
        return;
    }

    const view = group.querySelector('.view-mode');
    const edit = group.querySelector('.edit-mode');
    
    if (view.style.display === 'none') {
        view.style.display = 'block';
        edit.style.display = 'none';
    } else {
        view.style.display = 'none';
        edit.style.display = 'block';
    }
}
</script>
<script>
    // Exemple de script qui doit être présent sur TOUTES les pages
    const mobileToggle = document.querySelector('.mobile-toggle');
    const mobileDrawer = document.querySelector('.mobile-drawer');
    const closeDrawer = document.querySelector('.close-drawer');

    if (mobileToggle && mobileDrawer) {
        mobileToggle.addEventListener('click', function() {
            mobileDrawer.classList.toggle('open');
        });
    }
    if (closeDrawer && mobileDrawer) {
        closeDrawer.addEventListener('click', function() {
            mobileDrawer.classList.remove('open');
        });
    }

const toggleButtons = document.querySelectorAll('.toggle-submenu');

    toggleButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const parentLi = this.closest('li');
            const submenu = parentLi.querySelector('.submenu-mobile');

            if (submenu) {
                // On active/désactive le sous-menu
                submenu.classList.toggle('active');
                // On change le texte du bouton (+ ou -)
                this.textContent = submenu.classList.contains('active') ? '−' : '+';
            }
        });
    });
</script>

<?php include 'footer.php'; ?>
