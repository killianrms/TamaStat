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

        if ($utilisateur && password_verify($password, $utilisateur['mot_de_passe'])) {
            $_SESSION['user'] = [
                'id' => $utilisateur['id'],
                'nom_utilisateur' => $utilisateur['nom_utilisateur'],
                'email' => $utilisateur['email'],
                'role' => $utilisateur['role'],
                'is_admin' => isset($utilisateur['is_admin']) ? (int)$utilisateur['is_admin'] : 0,
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
        $stmt = $this->pdo->prepare("SELECT taille, prix_par_m3, nombre_box FROM boxes_utilisateur WHERE utilisateur_id = :userId");
        $stmt->execute(['userId' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: [];
    }


    public function mettreAJourDonneesUtilisateur($utilisateurId, $prixParM3, $boxes)
    {
        if ($utilisateurId === null) {
            echo "Erreur : l'ID utilisateur est manquant.";
            exit;
        }

        $pdo = $this->pdo;

        $updatePrixQuery = "UPDATE boxes_utilisateur SET prix_par_m3 = :prix_par_m3 WHERE utilisateur_id = :utilisateur_id";
        $updatePrixStmt = $pdo->prepare($updatePrixQuery);
        $updatePrixStmt->execute([
            ':prix_par_m3' => $prixParM3,
            ':utilisateur_id' => $utilisateurId
        ]);

        foreach ($boxes as $taille => $nombreBox) {
            $taille = (float) $taille;
            $nombreBox = (int) $nombreBox;

            $query = "SELECT COUNT(*) FROM boxes_utilisateur WHERE utilisateur_id = :utilisateur_id AND taille = :taille";
            $stmt = $pdo->prepare($query);
            $stmt->execute([':utilisateur_id' => $utilisateurId, ':taille' => $taille]);
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                $updateQuery = "UPDATE boxes_utilisateur SET nombre_box = :nombre_box WHERE utilisateur_id = :utilisateur_id AND taille = :taille";
                $updateStmt = $pdo->prepare($updateQuery);
                $updateStmt->execute([
                    ':nombre_box' => $nombreBox,
                    ':utilisateur_id' => $utilisateurId,
                    ':taille' => $taille
                ]);
            } else {
                $insertQuery = "INSERT INTO boxes_utilisateur (utilisateur_id, taille, nombre_box, prix_par_m3) VALUES (:utilisateur_id, :taille, :nombre_box, :prix_par_m3)";
                $insertStmt = $pdo->prepare($insertQuery);
                $insertStmt->execute([
                    ':utilisateur_id' => $utilisateurId,
                    ':taille' => $taille,
                    ':nombre_box' => $nombreBox,
                    ':prix_par_m3' => $prixParM3
                ]);
            }
        }
    }
}

