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

    public function importerFactures($csvFile, $utilisateurId) {
        $fileExt = strtolower(pathinfo($csvFile['name'], PATHINFO_EXTENSION));
        if ($fileExt !== 'csv') {
            throw new Exception("Le fichier doit être au format CSV.");
        }

        $fileTmpName = $csvFile['tmp_name'];

        if (($handle = fopen($fileTmpName, 'r')) !== false) {
            fgetcsv($handle);

            while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                if (count($data) >= 13) {
                    $this->importerFacture($utilisateurId, $data);
                }
            }

            fclose($handle);
        } else {
            throw new Exception("Erreur lors de l'ouverture du fichier.");
        }
    }

    public function importerFacture($utilisateurId, $ligne) {
        try {
            $dateFacture = DateTime::createFromFormat('d/m/Y', $ligne[9]);
            if (!$dateFacture) {
                throw new Exception("Date de facture invalide : " . $ligne[9]);
            }

            $stmt = $this->pdo->prepare('
            INSERT INTO factures 
            (reference_contrat, utilisateur_id, titre, parc, client_nom, client_prenom, total_ht, tva, total_ttc, date_facture, adresse, code_postal, ville)
            VALUES 
            (:reference_contrat, :utilisateur_id, :titre, :parc, :client_nom, :client_prenom, :total_ht, :tva, :total_ttc, :date_facture, :adresse, :code_postal, :ville)
        ');

            $stmt->execute([
                ':reference_contrat' => $ligne[0],
                ':utilisateur_id' => $utilisateurId,
                ':titre' => $ligne[1],
                ':parc' => $ligne[2],
                ':client_nom' => $ligne[3],
                ':client_prenom' => $ligne[4],
                ':total_ht' => str_replace(',', '.', $ligne[5]),
                ':tva' => str_replace(',', '.', $ligne[6]),
                ':total_ttc' => str_replace(',', '.', $ligne[7]),
                ':date_facture' => $dateFacture->format('Y-m-d'),
                ':adresse' => $ligne[10],
                ':code_postal' => $ligne[11],
                ':ville' => $ligne[12]
            ]);
        } catch (PDOException $e) {
            throw new Exception("Erreur PDO : " . $e->getMessage());
        }
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
            (denomination, prix_ttc, utilisateur_id)
            VALUES 
            (:denomination, :prix_ttc, :utilisateur_id)
        ');

            $denomination = $this->normalizeString($ligne[1]);

            if (empty($denomination)) {
                throw new Exception("Dénomination manquante.");
            }

            $prixTtc = floatval(str_replace(',', '.', $ligne[3]));

            $stmt->execute([
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
            stream_filter_prepend($handle, 'convert.iconv.ISO-8859-1/UTF-8');
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

            $clientNom = mb_convert_encoding(trim($ligne[2] . ' ' . $ligne[3]), 'UTF-8', 'ISO-8859-1');

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
                'client_nom' => $clientNom, // Encodé en UTF-8
                'date_debut' => $dateDebut->format('Y-m-d'),
                'date_fin' => $dateFin ? $dateFin->format('Y-m-d') : null
            ]);
        } catch (\PDOException $e) {
            throw new Exception("Erreur PDO : " . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception("Erreur : " . $e->getMessage());
        }
    }


    public function getBoxTypeIdByReference($denomination, $utilisateurId) {
        $denomination = $this->normalizeString($denomination);

        $stmt = $this->pdo->prepare('SELECT id FROM box_types WHERE TRIM(denomination) = TRIM(:denomination) AND utilisateur_id = :utilisateur_id');
        $stmt->execute(['denomination' => $denomination, 'utilisateur_id' => $utilisateurId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['id'] : null;
    }

    private function normalizeString($string) {
        $string = trim($string);
        if (!mb_detect_encoding($string, 'UTF-8', true)) {
            $string = iconv('ISO-8859-1', 'UTF-8//TRANSLIT', $string);
        }
        $string = str_replace(["\xc2\xb0", "Â°"], "°", $string);
        $string = preg_replace('/\s+/', ' ', $string);
        return $string;
    }
}
