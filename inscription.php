<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        echo "Session expirée, merci de recharger la page.";
        exit;
    }

    $pseudo   = trim($_POST['pseudo'] ?? ''); 
    $email    = trim($_POST['email'] ?? '');
    $tel      = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($pseudo) || empty($email) || empty($password)) {
        echo "Veuillez remplir tous les champs obligatoires.";
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Le format de l'adresse email n'est pas valide.";
        exit;
    }

    $pass_hashed = password_hash($password, PASSWORD_DEFAULT);

    try {
        $check = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $check->execute([$email]);
        $existingUser = $check->fetch();

        if ($existingUser) {
            echo "Cet email est déjà utilisé par un autre compte.";
            exit;
        }

        $sql = "INSERT INTO utilisateurs (pseudo, email, telephone, mot_de_passe) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$pseudo, $email, $tel, $pass_hashed])) {
            
            $new_user_id = $pdo->lastInsertId();

            // SÉCURITÉ : On régénère l'ID de session
            session_regenerate_id(true); 

            // ON UTILISE LES MÊMES CLÉS QUE DANS LOGIN.PHP ET HEADER.PHP
            $_SESSION['user_id'] = $new_user_id; // Identifiant unique
            $_SESSION['id']      = $new_user_id; // Double sécurité pour certains scripts
            $_SESSION['pseudo']  = $pseudo;      // Nom d'affichage
            $_SESSION['email']   = $email;       // Email pour le profil

            echo "success"; 
            exit;
        }

        echo "Impossible de finaliser l'inscription pour le moment.";
        exit;
    } catch (PDOException $e) {
        error_log("Erreur Inscription : " . $e->getMessage());
        echo "Une erreur technique est survenue.";
        exit;
    }
}
