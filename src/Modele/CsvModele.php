<?php
namespace App\Modele;

use App\Configuration\ConnexionBD;
use PDO;

class CsvModele {
    private $pdo;

    public function __construct() {
        $connexion = new ConnexionBD();
        $this->pdo = $connexion->getPdo();
    }

    public function importerLocations($utilisateur_id, $ligne) {
        try {
            $stmt = $this->pdo->prepare('
            INSERT INTO locations 
            (reference, centre, type_tiers, nom_societe, prenom, telephone, mail, nb_produits, total_ttc, date_location, utilisateur_id)
            VALUES 
            (:reference, :centre, :type_tiers, :nom_societe, :prenom, :telephone, :mail, :nb_produits, :total_ttc, :date_location, :utilisateur_id)
        ');

            $stmt->execute([
                'reference' => $ligne[0],
                'centre' => $ligne[1],
                'type_tiers' => $ligne[2],
                'nom_societe' => $ligne[4],
                'prenom' => $ligne[5],
                'telephone' => $ligne[6],
                'mail' => $ligne[7],
                'nb_produits' => is_numeric($ligne[8]) ? (int)$ligne[8] : null,
                'total_ttc' => (float)str_replace(['€', '&euro;', ' '], '', $ligne[10]),
                'date_location' => \DateTime::createFromFormat('d/m/Y', $ligne[11])->format('Y-m-d'),
                'utilisateur_id' => $utilisateur_id
            ]);
            echo "Ligne insérée avec succès.<br>";
        } catch (\PDOException $e) {
            throw new \Exception("Erreur PDO : " . $e->getMessage());
        }
    }

    public function getLocationsByUser($utilisateur_id) {
        $stmt = $this->pdo->prepare('SELECT * FROM locations WHERE utilisateur_id = :utilisateur_id');
        $stmt->execute(['utilisateur_id' => $utilisateur_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>