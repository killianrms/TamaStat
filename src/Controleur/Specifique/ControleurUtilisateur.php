<?php

namespace App\Controleur\Specifique;

use App\Configuration\ConnexionBD;
use PDO;

class ControleurUtilisateur
{
    private $pdo;

    public function __construct()
    {
        $connexion = new ConnexionBD();
        $this->pdo = $connexion->getPdo();
    }

    public function login($usernameOrEmail, $password)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM utilisateurs WHERE nom_utilisateur = :input OR email = :input');
        $stmt->execute(['input' => $usernameOrEmail]);

        $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($utilisateur && password_verify($password, $utilisateur['mot_de_passe'])) {
            $_SESSION['user'] = [
                'id' => $utilisateur['id'],
                'nom_utilisateur' => $utilisateur['nom_utilisateur'],
                'email' => $utilisateur['email'],
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

    public function getDonneesUtilisateur($userId)
    {
        $stmt = $this->pdo->prepare("SELECT box_type_id, quantite FROM utilisateur_boxes WHERE utilisateur_id = :userId");
        $stmt->execute(['userId' => $userId]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result ?: [];
    }

    public function mettreAJourDonneesUtilisateur($utilisateurId, array $boxes)
    {
        if ($utilisateurId === null) {
            throw new \Exception("Erreur : l'ID utilisateur est manquant.");
        }

        $pdo = $this->pdo;

        $deleteQuery = "DELETE FROM utilisateur_boxes WHERE utilisateur_id = :utilisateur_id";
        $deleteStmt = $pdo->prepare($deleteQuery);
        $deleteStmt->execute([':utilisateur_id' => $utilisateurId]);

        $insertQuery = "INSERT INTO utilisateur_boxes (utilisateur_id, box_type_id, quantite) VALUES (:utilisateur_id, :box_type_id, :quantite)";
        $insertStmt = $pdo->prepare($insertQuery);

        foreach ($boxes as $boxTypeId => $quantite) {
            if ($quantite >= 0) {
                $insertStmt->execute([
                    ':utilisateur_id' => $utilisateurId,
                    ':box_type_id' => $boxTypeId,
                    ':quantite' => $quantite,
                ]);
            }
        }
    }
}
