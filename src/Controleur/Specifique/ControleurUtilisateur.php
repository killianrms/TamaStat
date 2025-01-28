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

        if ($utilisateur && $password === $utilisateur['mot_de_passe']) {
            $_SESSION['user'] = [
                'id' => $utilisateur['id'],
                'nom_utilisateur' => $utilisateur['nom_utilisateur'],
                'email' => $utilisateur['email'],
                'role' => $utilisateur['role']
            ];

            header('Location: routeur.php?route=accueil');
            exit;
        } else {
            $_SESSION['erreur_connexion'] = "Identifiant ou mot de passe incorrect";

            header('Location: routeur.php?route=connexion');
            exit;
        }
    }





    public function deconnexion() {
        session_destroy();
        header('Location: routeur.php?route=connexion');
        exit;
    }

    public function getDonneesUtilisateur($userId) {
        $stmt = $this->pdo->prepare("SELECT taille, prix_par_m3, nombre_box FROM user_box WHERE utilisateur_id = :userId");
        $stmt->execute(['userId' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: [];
    }


    public function mettreAJourDonneesUtilisateur($userId, $taille, $prixParM3, $nombreBox) {
        $pdo = (new ConnexionBD())->getPdo();

        $sql = "SELECT COUNT(*) FROM user_box WHERE utilisateur_id = :userId";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['userId' => $userId]);
        $exists = $stmt->fetchColumn() > 0;

        if ($exists) {
            $sql = "UPDATE user_box 
                SET taille = :taille, prix_par_m3 = :prixParM3, nombre_box = :nombreBox 
                WHERE utilisateur_id = :userId";
        } else {
            $sql = "INSERT INTO user_box (utilisateur_id, taille, prix_par_m3, nombre_box) 
                VALUES (:userId, :taille, :prixParM3, :nombreBox)";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'userId' => $userId,
            'taille' => $taille,
            'prixParM3' => $prixParM3,
            'nombreBox' => $nombreBox
        ]);
    }

}

