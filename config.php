<?php
// Configuration de l'environnement
$environment = 'development'; // Changer à 'production' en ligne

// Gestion des erreurs selon l'environnement
if ($environment === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
}

// Configuration des paramètres de la base de données
$host = 'localhost';
$dbname = 'bigmonkey_db';
$user = 'root';
$password = ''; // Pensez à mettre un mot de passe fort en production

try {
    // Utilisation de utf8mb4 pour une compatibilité totale
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    
    // Options PDO pour la sécurité et la praticité
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, $user, $password, $options);

} catch (PDOException $e) {
    error_log($e->getMessage());
    
    if ($environment === 'development') {
        die("Erreur de connexion : " . $e->getMessage());
    } else {
        die("Une erreur est survenue lors de la connexion à la base de données.");
    }
}
?>