<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ob_start();

include __DIR__ . '/../src/Vue/utilisateur/header.php';

use App\Controleur\Specifique\ControleurUtilisateur;
use App\Controleur\Specifique\ControleurCsv;
use App\Configuration\ConnexionBD;

require_once __DIR__ . '/../vendor/autoload.php';

$route = $_GET['route'] ?? 'connexion';

$controleurUtilisateur = new ControleurUtilisateur();
$controleurCsv = new ControleurCsv();

echo '<link rel="stylesheet" href="../ressources/css/style.css">';

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

        case 'ajouterUtilisateur':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $nom_utilisateur = htmlspecialchars($_POST['nom_utilisateur']);
                $mot_de_passe = $_POST['mot_de_passe'];
                $email = $_POST['email'];
                $is_admin = $_POST['is_admin'];

                $pdo = (new ConnexionBD())->getPdo();
                $stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE nom_utilisateur = :nom_utilisateur OR email = :email');
                $stmt->execute(['nom_utilisateur' => $nom_utilisateur, 'email' => $email]);
                $utilisateurExist = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($utilisateurExist) {
                    header('Location: routeur.php?route=erreur&message=Cet utilisateur existe déjà.');
                    exit;
                } else {
                    $mot_de_passe_hache = password_hash($mot_de_passe, PASSWORD_BCRYPT);

                    $stmt = $pdo->prepare('INSERT INTO utilisateurs (nom_utilisateur, mot_de_passe, email, is_admin) VALUES (:nom_utilisateur, :mot_de_passe, :email, :is_admin)');
                    $stmt->execute([
                        ':nom_utilisateur' => $nom_utilisateur,
                        ':mot_de_passe' => $mot_de_passe_hache,
                        ':email' => $email,
                        ':is_admin' => $is_admin
                    ]);
                    echo('Utilisateur ajouté avec succès');
                    exit;
                }
            }
            break;

        case 'ajouterDonneesAccueil':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $quantitesBox = [];
                $tailles = [1, 1.5, 2, 2.5, 3, 4, 5, 6, 7, 8, 9, 10];

                foreach ($tailles as $tailleBox) {
                    $quantitesBox[$tailleBox] = isset($_POST["box_$tailleBox"]) ? intval($_POST["box_$tailleBox"]) : 0;
                }

                $prixParM3 = isset($_POST['prix_par_m3']) ? floatval($_POST['prix_par_m3']) : 0;

                foreach ($quantitesBox as $taille => $nombreBox) {
                    if ($nombreBox > 0) {
                        $controleurUtilisateur->mettreAJourDonneesUtilisateur(
                            $_SESSION['user']['id'],
                            $taille,
                            $prixParM3,
                            $nombreBox
                        );
                    }
                }

                header('Location: routeur.php?route=accueil');
                exit;
            } else {
                $donneesUtilisateur = $controleurUtilisateur->getDonneesUtilisateur($_SESSION['user']['id']);
                require_once __DIR__ . '/../src/Vue/utilisateur/ajouterDonnees.php';
            }
            break;

        case 'gestionUtilisateurs':
            if (!isset($_SESSION['user']) || $_SESSION['user']['is_admin'] !== 1) {
                echo "Accès non autorisé!";
                exit;
            }
            require_once __DIR__ . '/../src/Vue/utilisateur/gestionUtilisateurs.php';
            break;

        case 'modifierUtilisateur':
            if (!isset($_SESSION['user']) || $_SESSION['user']['is_admin'] !== 1) {
                echo "Accès non autorisé!";
                exit;
            }
            $id = $_GET['id'] ?? null;
            if ($id) {
                require_once __DIR__ . '/../src/Vue/utilisateur/modifierUtilisateur.php';
            }
            break;

        case 'supprimerUtilisateur':
            if (!isset($_SESSION['user']) || $_SESSION['user']['is_admin'] !== 1) {
                echo "Accès non autorisé!";
                exit;
            }
            $id = $_GET['id'] ?? null;
            if ($id) {
                $pdo = (new ConnexionBD())->getPdo();
                $stmt = $pdo->prepare('DELETE FROM utilisateurs WHERE id = :id');
                $stmt->execute(['id' => $id]);
                header('Location: routeur.php?route=gestionUtilisateurs');
                exit;
            }
            break;

        case 'deconnexion':
            session_unset();
            session_destroy();
            header('Location: routeur.php?route=connexion');
            exit;
            break;

        case 'cgu':
            require_once __DIR__ . '/../Legal/cgu.php';
            break;

        case 'mentions-legales':
            require_once __DIR__ . '/../Legal/mentions-legales.php';
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

