<?php
class ConnexionUtilisateur {
    private $pdo;

    public function __construct() {
        $this->pdo = new PDO('mysql:host=localhost;dbname=ton_db', 'root', '');
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

}

