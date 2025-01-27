<?php
require_once __DIR__ . '/../src/Controleur/Specifique/ControleurUtilisateur.php';
require_once __DIR__ . '/../src/Controleur/Specifique/ControleurCsv.php'; // Ajoutez le contrôleur pour l'ajout des données CSV

$route = $_GET['route'] ?? 'connexion';

$controleurUtilisateur = new ControleurUtilisateur();
$controleurCsv = new ControleurCsv(); // Création d'une instance de ControleurCsv pour gérer l'ajout des données

try {
    switch ($route) {
        case 'connexion':
            require_once __DIR__ . '/../src/Vue/utilisateur/formulaireConnexion.php';
            break;

        case 'login':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $username = $_POST['username'] ?? '';
                $password = $_POST['password'] ?? '';
                $controleurUtilisateur->login($username, $password);
            }
            break;

        case 'accueil':
            require_once __DIR__ . '/../src/Vue/utilisateur/accueil.php';
            break;

        case 'ajouterDonnees':
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
                $csvFile = $_FILES['csv_file'];
                $controleurCsv->ajouterDonneesDepuisFichier($csvFile);
                header('Location: routeur.php?route=stats');
                exit;
            }
            break;

        case 'deconnexion':
            $controleurUtilisateur->deconnexion();
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
