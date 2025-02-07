<?php
namespace App\Modele;

use App\Configuration\ConnexionBD;
use PDO;
use Exception;

class CsvModele {
    private $pdo;

    public function __construct() {
        $connexion = new ConnexionBD();
        $this->pdo = $connexion->getPdo();
    }

    public function importerBoxType($ligne) {
        try {
            $stmt = $this->pdo->prepare('
            INSERT INTO box_types 
            (reference, denomination, taille_m3, prix_ttc, couleur)
            VALUES 
            (:reference, :denomination, :taille_m3, :prix_ttc, :couleur)
        ');

            $stmt->execute([
                'reference' => $ligne[0],
                'denomination' => $ligne[1],
                'taille_m3' => $ligne[2],
                'prix_ttc' => $ligne[3],
                'couleur' => $ligne[4]
            ]);
        } catch (\PDOException $e) {
            throw new Exception("Erreur PDO : " . $e->getMessage());
        }
    }

    public function importerLocation($utilisateurId, $ligne) {
        try {
            $stmt = $this->pdo->prepare('
            INSERT INTO locations 
            (reference_contrat, utilisateur_id, box_type_id, client_nom, date_debut, date_fin)
            VALUES 
            (:reference_contrat, :utilisateur_id, :box_type_id, :client_nom, :date_debut, :date_fin)
        ');

            $stmt->execute([
                'reference_contrat' => $ligne[0],
                'utilisateur_id' => $utilisateurId,
                'box_type_id' => $ligne[1],
                'client_nom' => $ligne[2],
                'date_debut' => $ligne[3],
                'date_fin' => $ligne[4]
            ]);
        } catch (\PDOException $e) {
            throw new Exception("Erreur PDO : " . $e->getMessage());
        }
    }

    public function getLocationsByUser($utilisateur_id) {
        $stmt = $this->pdo->prepare('SELECT * FROM locations WHERE utilisateur_id = :utilisateur_id');
        $stmt->execute(['utilisateur_id' => $utilisateur_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
