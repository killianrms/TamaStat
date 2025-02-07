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

    public function importerBoxTypes($csvFile, $utilisateurId) {
        $fileExt = strtolower(pathinfo($csvFile['name'], PATHINFO_EXTENSION));
        if ($fileExt !== 'csv') {
            throw new Exception("Le fichier doit être au format CSV.");
        }

        $fileTmpName = $csvFile['tmp_name'];

        if (($handle = fopen($fileTmpName, 'r')) !== false) {
            fgetcsv($handle);

            while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                if (count($data) >= 5) {
                    $this->importerBoxType($data, $utilisateurId);
                }
            }

            fclose($handle);
        } else {
            throw new Exception("Erreur lors de l'ouverture du fichier.");
        }
    }

    private function importerBoxType($ligne, $utilisateurId) {
        try {
            $stmt = $this->pdo->prepare('
            INSERT INTO box_types 
            (reference, denomination, taille_m3, prix_ttc, couleur, utilisateur_id)
            VALUES 
            (:reference, :denomination, :taille_m3, :prix_ttc, :couleur, :utilisateur_id)
        ');

            $stmt->execute([
                'reference' => $ligne[0],
                'denomination' => $ligne[1],
                'taille_m3' => floatval(str_replace(',', '.', $ligne[2])),
                'prix_ttc' => floatval(str_replace(',', '.', $ligne[3])),
                'couleur' => $ligne[4],
                'utilisateur_id' => $utilisateurId
            ]);
        } catch (\PDOException $e) {
            throw new Exception("Erreur PDO : " . $e->getMessage());
        }
    }

    public function importerContrats($csvFile, $utilisateurId) {
        $fileExt = strtolower(pathinfo($csvFile['name'], PATHINFO_EXTENSION));
        if ($fileExt !== 'csv') {
            throw new Exception("Le fichier doit être au format CSV.");
        }

        $fileTmpName = $csvFile['tmp_name'];

        if (($handle = fopen($fileTmpName, 'r')) !== false) {
            fgetcsv($handle); // Ignorer l'en-tête

            while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                if (count($data) >= 12) {
                    $this->importerLocation($utilisateurId, $data);
                }
            }

            fclose($handle);
        } else {
            throw new Exception("Erreur lors de l'ouverture du fichier.");
        }
    }

    private function importerLocation($utilisateurId, $ligne) {
        try {
            $stmt = $this->pdo->prepare('
            INSERT INTO locations 
            (reference_contrat, utilisateur_id, box_type_id, client_nom, date_debut, date_fin)
            VALUES 
            (:reference_contrat, :utilisateur_id, :box_type_id, :client_nom, :date_debut, :date_fin)
        ');

            $stmt->execute([
                'reference_contrat' => $ligne[1],
                'utilisateur_id' => $utilisateurId,
                'box_type_id' => $this->getBoxTypeIdByReference($ligne[8], $utilisateurId),
                'client_nom' => $ligne[2] . ' ' . $ligne[3],
                'date_debut' => \DateTime::createFromFormat('d/m/Y', $ligne[10])->format('Y-m-d'),
                'date_fin' => $ligne[11] ? \DateTime::createFromFormat('d/m/Y', $ligne[11])->format('Y-m-d') : null
            ]);
        } catch (\PDOException $e) {
            throw new Exception("Erreur PDO : " . $e->getMessage());
        }
    }

    private function getBoxTypeIdByReference($reference, $utilisateurId) {
        $stmt = $this->pdo->prepare('SELECT id FROM box_types WHERE reference = :reference AND utilisateur_id = :utilisateur_id');
        $stmt->execute(['reference' => $reference, 'utilisateur_id' => $utilisateurId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['id'] : null;
    }
}
