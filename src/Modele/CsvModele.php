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

    public function importerBoxType($ligne, $utilisateurId) {
        try {
            $stmt = $this->pdo->prepare('
            INSERT INTO box_types 
            (reference, denomination, prix_ttc, utilisateur_id)
            VALUES 
            (:reference, :denomination, :prix_ttc, :utilisateur_id)
        ');

            $denomination = !empty($ligne[2]) ? $ligne[2] : $ligne[1];
            $denomination = mb_convert_encoding($denomination, 'UTF-8', 'auto');
            $denomination = htmlspecialchars($denomination, ENT_QUOTES, 'UTF-8');

            $stmt->execute([
                'reference' => $ligne[0],
                'denomination' => $denomination,
                'prix_ttc' => floatval(str_replace(',', '.', $ligne[3])),
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

    public function importerLocation($utilisateurId, $ligne) {
        try {
            if (empty($ligne[10]) || !\DateTime::createFromFormat('d/m/Y', $ligne[10])) {
                throw new Exception("Date invalide pour 'date_debut' : " . $ligne[10]);
            }

            $dateDebut = \DateTime::createFromFormat('d/m/Y', $ligne[10]);

            $dateFin = null;
            if (!empty($ligne[11]) && \DateTime::createFromFormat('d/m/Y', $ligne[11])) {
                $dateFin = \DateTime::createFromFormat('d/m/Y', $ligne[11]);
            }

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
                'date_debut' => $dateDebut->format('Y-m-d'),
                'date_fin' => $dateFin ? $dateFin->format('Y-m-d') : null
            ]);
        } catch (\PDOException $e) {
            throw new Exception("Erreur PDO : " . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception("Erreur de date : " . $e->getMessage());
        }
    }

    private function getBoxTypeIdByReference($reference, $utilisateurId) {
        $stmt = $this->pdo->prepare('SELECT id FROM box_types WHERE reference = :reference AND utilisateur_id = :utilisateur_id');
        $stmt->execute(['reference' => $reference, 'utilisateur_id' => $utilisateurId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['id'] : null;
    }
}
