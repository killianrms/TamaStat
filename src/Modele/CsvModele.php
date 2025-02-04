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

    public function importerLocations($utilisateur_id, $ligne) {
        try {
            $stmt = $this->pdo->prepare('
            INSERT INTO locations 
            (reference, centre, box_reference, nom_societe, prenom, telephone, mail, nb_produits, total_ttc, date_location, utilisateur_id)
            VALUES 
            (:reference, :centre, :box_reference, :nom_societe, :prenom, :telephone, :mail, :nb_produits, :total_ttc, :date_location, :utilisateur_id)
        ');

            $nb_produits = is_numeric($ligne[6]) ? (int)$ligne[6] : null;
            $prix_ttc = preg_replace('/[^0-9.,]/', '', explode(' ', $ligne[10])[0]);

            $date_location = \DateTime::createFromFormat('d/m/Y', $ligne[11]);
            $date_location = $date_location ? $date_location->format('Y-m-d') : null;

            $box_reference = $ligne[8];

            $stmt->execute([
                'reference' => $ligne[1],
                'centre' => $ligne[7],
                'box_reference' => $box_reference,
                'nom_societe' => $ligne[3],
                'prenom' => $ligne[4],
                'telephone' => $ligne[5],
                'mail' => $ligne[6],
                'nb_produits' => $nb_produits,
                'total_ttc' => (float)str_replace(',', '.', $prix_ttc),
                'date_location' => $date_location,
                'utilisateur_id' => $utilisateur_id
            ]);

            echo "Ligne insérée avec succès.<br>";
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
?>
