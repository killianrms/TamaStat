<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ob_start();

include __DIR__ . '/../src/Vue/utilisateur/header.php';

use App\Controleur\Specifique\ControleurUtilisateur;
use App\Controleur\Specifique\ControleurCsv;

require_once __DIR__ . '/../src/Controleur/Specifique/ControleurUtilisateur.php';
require_once __DIR__ . '/../src/Controleur/Specifique/ControleurCsv.php';
require_once __DIR__ . '/../vendor/autoload.php';

echo '<link rel="stylesheet" href="../ressources/css/style.css">';

$route = $_GET['route'] ?? 'connexion';

$controleurUtilisateur = new ControleurUtilisateur();
$controleurCsv = new ControleurCsv();

try {
    switch ($route) {
        case 'connexion':
            if (isset($_SESSION['user'])) {
                header('Location: routeur.php?route=accueil');
                exit;
            }
            require_once __DIR__ . '/../src/Vue/utilisateur/formulaireConnexion.php';
            break;

        case 'login':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $username = $_POST['username'] ?? '';
                $password = $_POST['password'] ?? '';
                $controleurUtilisateur->login($username, $password);
            }
            break;

        case 'stats':
            if (!isset($_SESSION['user'])) {
                header('Location: routeur.php?route=connexion');
                exit;
            }
            require_once __DIR__ . '/../src/Vue/utilisateur/stats.php';
            break;

        case 'accueil':
            if (!isset($_SESSION['user'])) {
                header('Location: routeur.php?route=connexion');
                exit;
            }
            require_once __DIR__ . '/../src/Vue/utilisateur/accueil.php';
            break;

        case 'ajouterDonneesAccueil':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $quantitesBox = [];
                $tailles = [1, 1.5, 2, 2.5, 3, 4, 5, 6, 7, 8, 9, 10];

                foreach ($tailles as $tailleBox) {
                    $quantitesBox[$tailleBox] = $_POST["box_$tailleBox"] ?? 0;
                }

                foreach ($quantitesBox as $taille => $nombreBox) {
                    if ($nombreBox > 0) {
                        $controleurUtilisateur->mettreAJourDonneesUtilisateur(
                            $_SESSION['user']['id'],
                            $taille,
                            $_POST['prix_par_m3'],
                            $nombreBox
                        );
                    }
                }

                header('Location: routeur.php?route=accueil');
                exit;
            }
            break;



        case 'deconnexion':
            session_unset();
            session_destroy();
            header('Location: routeur.php?route=connexion');
            exit;
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

include __DIR__ . '/../src/Vue/utilisateur/footer.php';

function autoload($class) {
    $classPath = __DIR__ . '/../src/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($classPath)) {
        require_once $classPath;
    }
}

spl_autoload_register('autoload');
