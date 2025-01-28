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

    public function recupererDonneesUtilisateur($utilisateurId) {
        $connexion = new ConnexionBD();
        $pdo = $connexion->getPdo();

        $stmt = $pdo->prepare('SELECT nombre_box, taille, prix_par_m3 FROM user_box WHERE utilisateur_id = :utilisateur_id');
        $stmt->bindParam(':utilisateur_id', $utilisateurId);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function mettreAJourDonneesUtilisateur($nombreBox, $tailleTotal, $prixParM3, $utilisateurId) {
        $connexion = new ConnexionBD();
        $pdo = $connexion->getPdo();

        $stmt = $pdo->prepare('
            UPDATE user_box 
            SET nombre_box = :nombre_box, taille = :taille, prix_par_m3 = :prix_par_m3
            WHERE utilisateur_id = :utilisateur_id
        ');

        $stmt->bindParam(':nombre_box', $nombreBox);
        $stmt->bindParam(':taille', $tailleTotal);
        $stmt->bindParam(':prix_par_m3', $prixParM3);
        $stmt->bindParam(':utilisateur_id', $utilisateurId);

        $stmt->execute();
    }
}

