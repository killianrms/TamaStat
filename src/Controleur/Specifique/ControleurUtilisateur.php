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
        $stmt = $this->pdo->prepare("SELECT taille, prix_par_m3, nombre_box FROM boxes_utilisateur WHERE utilisateur_id = :userId");
        $stmt->execute(['userId' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: [];
    }


    public function mettreAJourDonneesUtilisateur($utilisateurId, $taille, $prixParM3, $nombreBox)
    {
        if ($utilisateurId === null || $taille === null || $prixParM3 === null || $nombreBox === null) {
            echo "Erreur : des paramètres nécessaires sont manquants.";
            exit;
        }

        if (!is_numeric($prixParM3) || $prixParM3 < 0) {
            echo "Erreur : le prix par m³ doit être un nombre positif.";
            exit;
        }

        if (!is_numeric($nombreBox) || $nombreBox < 0) {
            echo "Erreur : le nombre de box doit être un nombre positif.";
            exit;
        }

        $taille = (float) $taille;
        $prixParM3 = (float) $prixParM3;
        $nombreBox = (int) round($nombreBox);

        $pdo = $this->pdo;

        $query = "SELECT COUNT(*) FROM boxes_utilisateur WHERE utilisateur_id = :utilisateur_id AND taille = :taille";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':utilisateur_id' => $utilisateurId, ':taille' => $taille]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            $updateQuery = "UPDATE boxes_utilisateur SET nombre_box = :nombre_box, prix_par_m3 = :prix_par_m3 WHERE utilisateur_id = :utilisateur_id AND taille = :taille";
            $updateStmt = $pdo->prepare($updateQuery);
            $updateStmt->execute([
                ':nombre_box' => $nombreBox,
                ':prix_par_m3' => $prixParM3,
                ':utilisateur_id' => $utilisateurId,
                ':taille' => $taille
            ]);

            if ($updateStmt->rowCount() > 0) {
                echo "Données mises à jour avec succès!";
            } else {
                echo "Aucune mise à jour effectuée. Vérifie les valeurs envoyées.";
            }
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

