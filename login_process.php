<?php
declare(strict_types=1);

// 1. Initialisation de la session en tout premier
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        echo "Session expirée, merci de recharger la page.";
        exit;
    }

    // Nettoyage des entrées pour éviter les espaces accidentels
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $remember = isset($_POST['remember']); // Vérifie si la case est cochée

    if (!empty($email) && !empty($password)) {
        try {
            // Recherche de l'utilisateur par email dans la table 'utilisateurs'
            $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // Utilisation du nom de colonne exact 'mot_de_passe'
                $hash = $user['mot_de_passe']; 

                if (password_verify($password, $hash)) {
                    // --- CONNEXION RÉUSSIE ---
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['id'] = $user['id'];
                    $_SESSION['pseudo'] = $user['pseudo']; 
                    $_SESSION['email'] = $user['email'];

                    // Gestion du "Se souvenir de moi" (Cookies valables 30 jours)
                    if ($remember) {
                        setcookie('user_email', $email, [
                            'expires' => time() + (3600 * 24 * 30),
                            'path' => '/',
                            'httponly' => true,
                            'samesite' => 'Lax',
                        ]);
                    } else {
                        // On supprime les cookies si la case n'est pas cochée
                        setcookie('user_email', '', [
                            'expires' => time() - 3600,
                            'path' => '/',
                            'httponly' => true,
                            'samesite' => 'Lax',
                        ]);
                    }

                    echo "success";
                } else {
                    echo "Le mot de passe saisi est incorrect.";
                }
            } else {
                echo "Aucun compte Big Monkey trouvé pour cet email.";
            }
        } catch (PDOException $e) {
            error_log("Erreur Connexion : " . $e->getMessage());
            echo "Une erreur technique est survenue.";
        }
    } else {
        echo "Veuillez remplir tous les champs.";
    }
}
?>
