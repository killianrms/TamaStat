<?php
// web/routeur.php

// Récupération de la route demandée
$route = $_GET['route'] ?? 'test'; // Par défaut, la route "test"

// Chargement du contrôleur frontal
try {
    switch ($route) {
        case 'test':
            require_once __DIR__ . '/../src/Vue/utilisateur/test.php';
            break;

        default:
            http_response_code(404);
            echo '<h1>Erreur 404 : Page non trouvée</h1>';
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo '<h1>Erreur interne du serveur</h1>';
    echo '<p>' . $e->getMessage() . '</p>';
}
