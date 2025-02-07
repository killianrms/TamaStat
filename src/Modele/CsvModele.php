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
            fgetcsv($handle, 1000, ';'); // Ignorer l'en-tête

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

            $reference = $this->normalizeString($ligne[0]);
            $denomination = $this->normalizeString($ligne[1]);

            if (empty($denomination)) {
                throw new Exception("Dénomination manquante pour la référence : $reference");
            }

            $prixTtc = floatval(str_replace(',', '.', $ligne[3]));

            $stmt->execute([
                'reference' => $reference,
                'denomination' => $denomination,
                'prix_ttc' => $prixTtc,
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
            fgetcsv($handle);

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
            $dateDebut = !empty($ligne[11]) && \DateTime::createFromFormat('d/m/Y', $ligne[11]) ? \DateTime::createFromFormat('d/m/Y', $ligne[11]) : null;
            $dateFin = !empty($ligne[12]) && \DateTime::createFromFormat('d/m/Y', $ligne[12]) ? \DateTime::createFromFormat('d/m/Y', $ligne[12]) : null;

            if (!$dateDebut) {
                throw new Exception("Date invalide pour 'date_debut' : " . $ligne[11]);
            }

            $boxTypeId = $this->getBoxTypeIdByReference($ligne[9], $utilisateurId);
            if (!$boxTypeId) {
                throw new Exception("Type de box non trouvé pour la référence : " . $ligne[9]);
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
                'box_type_id' => $boxTypeId,
                'client_nom' => $ligne[2] . ' ' . $ligne[3],
                'date_debut' => $dateDebut->format('Y-m-d'),
                'date_fin' => $dateFin ? $dateFin->format('Y-m-d') : null
            ]);
        } catch (\PDOException $e) {
            throw new Exception("Erreur PDO : " . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception("Erreur : " . $e->getMessage());
        }
    }

    public function getBoxTypeIdByReference($reference, $utilisateurId) {
        $reference = $this->normalizeString($reference);

        $stmt = $this->pdo->prepare('SELECT id FROM box_types WHERE REPLACE(reference, "Â\xc2°\xc2\xb0", "°") = REPLACE(:reference, "Â\xc2°\xc2\xb0", "°") AND utilisateur_id = :utilisateur_id');
        $stmt->execute(['reference' => $reference, 'utilisateur_id' => $utilisateurId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['id'] : null;
    }

    private function normalizeString($string) {
        $string = trim($string);
        if (!mb_detect_encoding($string, 'UTF-8', true)) {
            $string = iconv('ISO-8859-1', 'UTF-8//TRANSLIT', $string);
        }
        $string = str_replace(["\xc2\xb0", "Â\xc2°\xc2\xb0"], "°", $string);
        return $string;
    }
}
