<?php
namespace App\Controleur\Specifique;

use App\Configuration\ConnexionBD;
use PDO;

class ControleurUtilisateur {
    private $pdo;

    public function __construct() {
        $connexion = new ConnexionBD();
        $this->pdo = $connexion->getPdo();
    }

    public function login($usernameOrEmail, $password) {
        $stmt = $this->pdo->prepare('SELECT * FROM utilisateurs WHERE nom_utilisateur = :input OR email = :input');
        $stmt->execute(['input' => $usernameOrEmail]);

        $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($utilisateur) {
            $_SESSION['user'] = $utilisateur['nom_utilisateur'];
            header('Location: routeur.php?route=accueil');
            exit;
        } else {
            echo '<h2>Identifiants incorrects</h2>';
            require_once __DIR__ . '/../Vue/utilisateur/formulaireConnexion.php';
        }
    }

    public function deconnexion() {
        session_destroy();
        header('Location: routeur.php?route=connexion');
        exit;
    }

    public function getDonneesUtilisateur($userId) {
        $pdo = (new ConnexionBD())->getPdo();
        $sql = "SELECT nombre_de_box, taille_total, prix_par_m3 FROM utilisateurs WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $userId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function mettreAJourDonneesUtilisateur($nombreBox, $tailleTotal, $prixParM3, $userId) {
        $pdo = (new ConnexionBD())->getPdo();
        $sql = "UPDATE utilisateurs SET nombre_de_box = :nombreBox, taille_total = :tailleTotal, prix_par_m3 = :prixParM3 WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'nombreBox' => $nombreBox,
            'tailleTotal' => $tailleTotal,
            'prixParM3' => $prixParM3,
            'id' => $userId
        ]);
    }
}

