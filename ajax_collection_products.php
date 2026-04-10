<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';
require_once 'collection_helpers.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $context = getCollectionContextFromRequest($pdo, $_GET);
    $productsData = fetchCollectionProductsData($pdo, $context);
    $html = renderCollectionResultsHtml($context, $productsData, $_GET);

    echo json_encode([
        'success' => true,
        'html' => $html,
        'pageTitle' => $context['pageTitle'],
        'totalProducts' => $productsData['totalProducts'],
        'currentPage' => $productsData['currentPage'],
        'totalPages' => $productsData['totalPages'],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors du chargement des produits.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
