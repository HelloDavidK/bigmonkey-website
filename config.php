<?php
declare(strict_types=1);

// Configuration de l'environnement (APP_ENV=development|production)
$environment = getenv('APP_ENV') ?: 'development';

// Gestion des erreurs selon l'environnement
if ($environment === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
}

// Configuration BDD (peut venir des variables d'environnement en production)
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'bigmonkey_db';
$user = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';

try {
    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $pdo = new PDO($dsn, $user, $password, $options);
} catch (PDOException $e) {
    error_log('DB connection error: ' . $e->getMessage());

    if ($environment === 'development') {
        die("Erreur de connexion : " . $e->getMessage());
    }

    die("Une erreur est survenue lors de la connexion à la base de données.");
}
?>
