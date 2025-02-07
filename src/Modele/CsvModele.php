<?php

namespace App\Modele;

use App\Configuration\ConnexionBD;
use PDO;
use Exception;

class CsvModele
{
    private $pdo;

    public function __construct()
    {
        $connexion = new ConnexionBD();
        $this->pdo = $connexion->getPdo();
    }

    public function importerBoxTypes($csvFile, $utilisateurId)
    {
        $fileExt = strtolower(pathinfo($csvFile['name'], PATHINFO_EXTENSION));
        if ($fileExt !== 'csv') {
            throw new Exception("Le fichier doit être au format CSV.");
        }

        $fileTmpName = $csvFile['tmp_name'];

        if (($handle = fopen($fileTmpName, 'r')) !== false) {
            fgetcsv($handle, 1000, ';');

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

    public function importerBoxType($ligne, $utilisateurId)
    {
        try {
            $stmt = $this->pdo->prepare('
            INSERT INTO box_types 
            (reference, denomination, prix_ttc, utilisateur_id)
            VALUES 
            (:reference, :denomination, :prix_ttc, :utilisateur_id)
        ');

            $denomination = !empty($ligne[1]) ? $ligne[1] : 'Inconnu';
            $denomination = mb_convert_encoding($denomination, 'UTF-8', 'auto');
            $denomination = htmlspecialchars($denomination, ENT_QUOTES, 'UTF-8');

            $prixTtc = floatval(str_replace(',', '.', $ligne[3]));

            $stmt->execute([
                'reference' => $ligne[0],
                'denomination' => $denomination,
                'prix_ttc' => $prixTtc,
                'utilisateur_id' => $utilisateurId
            ]);
        } catch (\PDOException $e) {
            throw new Exception("Erreur PDO : " . $e->getMessage());
        }
    }

    public function importerLocation($utilisateurId, $ligne)
    {
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
}
