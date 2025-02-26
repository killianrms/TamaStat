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

    public function importerFacture($utilisateurId, $ligne) {
        try {
            $titre = $ligne[1];
            preg_match('/"([A-Z0-9]+)"/', $titre, $matches);
            $referenceContrat = $matches[1] ?? null;

            $estLieContrat = $referenceContrat && $this->contratExiste($referenceContrat, $utilisateurId);

            $dateFacture = \DateTime::createFromFormat('d/m/Y', $ligne[9]);
            if (!$dateFacture) {
                throw new Exception("Date de facture invalide : " . $ligne[9]);
            }

            $totalHt = str_replace(',', '.', $ligne[6]);
            $tva = str_replace(',', '.', $ligne[7]);
            $totalTtc = str_replace(',', '.', $ligne[8]);

            // Vérification si la facture existe déjà dans la BD
            if ($this->factureExiste($utilisateurId, $referenceContrat, $titre, $totalTtc, $dateFacture->format('Y-m-d'))) {
                return; // Si elle existe, on ne l'insère pas
            }

            $stmt = $this->pdo->prepare('
                INSERT INTO factures 
                (reference_contrat, utilisateur_id, titre, parc, total_ht, tva, total_ttc, date_facture, est_lie_contrat)
                VALUES 
                (:reference_contrat, :utilisateur_id, :titre, :parc, :total_ht, :tva, :total_ttc, :date_facture, :est_lie_contrat)
            ');

            $stmt->execute([
                ':reference_contrat' => $referenceContrat,
                ':utilisateur_id' => $utilisateurId,
                ':titre' => $titre,
                ':parc' => $ligne[2],
                ':total_ht' => $totalHt,
                ':tva' => $tva,
                ':total_ttc' => $totalTtc,
                ':date_facture' => $dateFacture->format('Y-m-d'),
                ':est_lie_contrat' => $estLieContrat ? 1 : 0
            ]);
        } catch (\PDOException $e) {
            throw new Exception("Erreur PDO : " . $e->getMessage());
        }
    }

    private function factureExiste($utilisateurId, $referenceContrat, $titre, $totalTtc, $dateFacture) {
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) FROM factures 
            WHERE utilisateur_id = :utilisateur_id 
            AND reference_contrat = :reference_contrat
            AND titre = :titre
            AND total_ttc = :total_ttc
            AND date_facture = :date_facture
        ');
        $stmt->execute([
            ':utilisateur_id' => $utilisateurId,
            ':reference_contrat' => $referenceContrat,
            ':titre' => $titre,
            ':total_ttc' => $totalTtc,
            ':date_facture' => $dateFacture
        ]);
        return $stmt->fetchColumn() > 0;
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

            // Vérification si la location existe déjà dans la BD
            if ($this->locationExiste($utilisateurId, $ligne[1], $boxTypeId, $clientNom, $dateDebut->format('Y-m-d'), $dateFin ? $dateFin->format('Y-m-d') : null)) {
                return; // Si elle existe, on ne l'insère pas
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
                'client_nom' => $clientNom,
                'date_debut' => $dateDebut->format('Y-m-d'),
                'date_fin' => $dateFin ? $dateFin->format('Y-m-d') : null
            ]);
        } catch (\PDOException $e) {
            throw new Exception("Erreur PDO : " . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception("Erreur : " . $e->getMessage());
        }
    }

    private function locationExiste($utilisateurId, $referenceContrat, $boxTypeId, $clientNom, $dateDebut, $dateFin) {
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) FROM locations 
            WHERE utilisateur_id = :utilisateur_id
            AND reference_contrat = :reference_contrat
            AND box_type_id = :box_type_id
            AND client_nom = :client_nom
            AND date_debut = :date_debut
            AND (date_fin = :date_fin OR (date_fin IS NULL AND :date_fin IS NULL))
        ');
        $stmt->execute([
            ':utilisateur_id' => $utilisateurId,
            ':reference_contrat' => $referenceContrat,
            ':box_type_id' => $boxTypeId,
            ':client_nom' => $clientNom,
            ':date_debut' => $dateDebut,
            ':date_fin' => $dateFin
        ]);
        return $stmt->fetchColumn() > 0;
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
