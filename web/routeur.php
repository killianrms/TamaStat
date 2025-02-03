<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_log("Données reçues : " . print_r($_POST, true));

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

$pdo = (new ConnexionBD())->getPdo();

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
            if (isset($_GET['reimport'])) {
                $pdo->prepare('DELETE FROM locations WHERE utilisateur_id = ?')->execute([$_SESSION['user']['id']]);
                header('Location: routeur.php?route=stats');
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
            if (!isset($_SESSION['user']) || $_SESSION['user']['is_admin'] !== 1) {
                echo "Accès non autorisé!";
                exit;
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $nom_utilisateur = htmlspecialchars($_POST['nom_utilisateur']);
                $mot_de_passe = $_POST['mot_de_passe'];
                $mot_de_passe_confirme = $_POST['mot_de_passe_confirme'];
                $email = $_POST['email'];
                $is_admin = $_POST['is_admin'];

                $erreurs = [];

                if ($mot_de_passe !== $mot_de_passe_confirme) {
                    $erreurs[] = "Les mots de passe ne correspondent pas.";
                }

                $pdo = (new ConnexionBD())->getPdo();
                $stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE email = :email OR nom_utilisateur = :nom_utilisateur');
                $stmt->execute(['email' => $email, 'nom_utilisateur' => $nom_utilisateur]);
                $utilisateurExist = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($utilisateurExist) {
                    $erreurs[] = "Cet utilisateur ou email existe déjà.";
                }

                if (empty($erreurs)) {
                    $mot_de_passe_hache = password_hash($mot_de_passe, PASSWORD_BCRYPT);

                    $stmt = $pdo->prepare('INSERT INTO utilisateurs (nom_utilisateur, mot_de_passe, email, is_admin) VALUES (:nom_utilisateur, :mot_de_passe, :email, :is_admin)');
                    $stmt->execute([
                        ':nom_utilisateur' => $nom_utilisateur,
                        ':mot_de_passe' => $mot_de_passe_hache,
                        ':email' => $email,
                        ':is_admin' => $is_admin
                    ]);

                    header('Location: routeur.php?route=gestionUtilisateurs&message=Utilisateur ajouté avec succès');
                    exit;
                } else {
                    $_SESSION['erreurs'] = $erreurs;
                }
            }

            require_once __DIR__ . '/../src/Vue/utilisateur/formulaireAjoutUtilisateur.php';
            break;


        case 'ajouterDonneesAccueil':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $quantitesBox = [];
                $tailles = range(1, 12);

                foreach ($tailles as $tailleBox) {
                    $quantitesBox[$tailleBox] = isset($_POST["box_$tailleBox"]) ? intval($_POST["box_$tailleBox"]) : 0;
                }

                $prixParM3 = isset($_POST['prix_par_m3']) ? floatval($_POST['prix_par_m3']) : 0;

                $controleurUtilisateur->mettreAJourDonneesUtilisateur(
                    $_SESSION['user']['id'],
                    $prixParM3,
                    $quantitesBox
                );

                header('Location: routeur.php?route=accueil');
                exit;
            } else {
                $donneesUtilisateur = $controleurUtilisateur->getDonneesUtilisateur($_SESSION['user']['id']);
                require_once __DIR__ . '/../src/Vue/utilisateur/accueil.php';
            }
            break;

        case 'modifierUtilisateur':
            if (!isset($_SESSION['user']) || $_SESSION['user']['is_admin'] !== 1) {
                header('Location: routeur.php?route=connexion');
                exit;
            }
            $id = $_GET['id'] ?? null;
            if ($id) {
                require_once __DIR__ . '/../src/Vue/utilisateur/modifierUtilisateur.php';
            }
            break;

        case 'supprimerUtilisateur':
            if (!isset($_SESSION['user']) || $_SESSION['user']['is_admin'] !== 1) {
                header('Location: routeur.php?route=connexion');
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

        case 'gestionUtilisateurs':
            if (!isset($_SESSION['user']) || $_SESSION['user']['is_admin'] !== 1) {
                header('Location: routeur.php?route=connexion');
                exit;
            }
            require_once __DIR__ . '/../src/Vue/utilisateur/gestionUtilisateurs.php';
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

