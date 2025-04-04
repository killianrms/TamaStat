<?php
namespace App\Modele;

use App\Configuration\ConnexionBD;
use PDO;

class ConnexionUtilisateur {
    private $pdo;

    public function __construct() {
        $connexion = new ConnexionBD();
        $this->pdo = $connexion->getPdo();
    }

    public function verifierUtilisateur($nom_utilisateur) {
        $stmt = $this->pdo->prepare('SELECT * FROM utilisateurs WHERE nom_utilisateur = :nom_utilisateur');
        $stmt->execute(['nom_utilisateur' => $nom_utilisateur]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function importerCsv() {
        if ($_FILES['fichier']['error'] === UPLOAD_ERR_OK) {
            $cheminFichier = $_FILES['fichier']['tmp_name'];
            $handle = fopen($cheminFichier, 'r');
            $modele = new DonneesCsvModele();

            while (($ligne = fgetcsv($handle, 1000, ',')) !== false) {
                $modele->ajouterDonnees($ligne);
            }
            fclose($handle);

            header('Location: /utilisateur/accueil?import=success');
            exit;
        } else {
            header('Location: /utilisateur/accueil?import=error');
            exit;
        }
    }

    public function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}