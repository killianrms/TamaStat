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

    public function login($usernameOrEmail, $password)
    {
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
            $_SESSION['erreur_connexion'] = 'Identifiant ou Mot de passe incorrect';
            header('Location: routeur.php?route=connexion&erreur=1');
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


    public function mettreAJourDonneesUtilisateur($idUtilisateur, $taille, $prixParM3, $nombreBox) {
        global $pdo;

        $stmt = $pdo->prepare('
        INSERT INTO boxes_utilisateur (utilisateur_id, taille, nombre_box, prix_par_m3)
        VALUES (:utilisateur_id, :taille, :nombre_box, :prix_par_m3)
        ON DUPLICATE KEY UPDATE nombre_box = :nombre_box, prix_par_m3 = :prix_par_m3
    ');

        $stmt->bindParam(':utilisateur_id', $idUtilisateur);
        $stmt->bindParam(':taille', $taille);
        $stmt->bindParam(':nombre_box', $nombreBox);
        $stmt->bindParam(':prix_par_m3', $prixParM3);

        $stmt->execute();
    }



}

