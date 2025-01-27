<?php
namespace App\Controleur\Specifique;

use App\Configuration\ConfigurationBaseDeDonnees;
use PDO;

class ControleurUtilisateur {
    private $pdo;

    public function __construct() {
        $host = ConfigurationBaseDeDonnees::getNomHote();
        $dbname = ConfigurationBaseDeDonnees::getNomBaseDeDonnees();
        $username = ConfigurationBaseDeDonnees::getLogin();
        $password = ConfigurationBaseDeDonnees::getPassword();
        $port = ConfigurationBaseDeDonnees::getPort();

        try {
            $dsn = "mysql:host=$host;port=$port;dbname=$dbname";
            $this->pdo = new PDO($dsn, $username, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Erreur de connexion à la base de données : " . $e->getMessage());
        }
    }

    public function login($username, $password) {
        $stmt = $this->pdo->prepare('SELECT * FROM utilisateurs WHERE username = :username');
        $stmt->execute(['username' => $username]);
        $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($utilisateur && password_verify($password, $utilisateur['password'])) {
            $_SESSION['user'] = $username;
            header('Location: routeur.php?route=accueil');
            exit;
        } else {
            echo '<h2>Identifiants incorrects</h2>';
            require_once __DIR__ . '/../../Vue/utilisateur/formulaireConnexion.php';
        }
    }

    public function deconnexion() {
        session_destroy();
        header('Location: routeur.php?route=connexion');
        exit;
    }
}
?>
