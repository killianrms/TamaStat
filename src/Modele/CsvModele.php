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

    /**
     * Vérifie si une facture existe déjà avant l'insertion.
     */
    private function factureExiste($utilisateurId, $referenceContrat, $titre, $dateFacture) {
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) FROM factures 
            WHERE utilisateur_id = :utilisateur_id 
            AND reference_contrat = :reference_contrat
            AND titre = :titre
            AND date_facture = :date_facture
        ');
        $stmt->execute([
            ':utilisateur_id' => $utilisateurId,
            ':reference_contrat' => $referenceContrat,
            ':titre' => $titre,
            ':date_facture' => $dateFacture
        ]);
        return $stmt->fetchColumn() > 0;
    }


    /**
     * Importe une facture si elle n'existe pas déjà.
     */
    public function importerFacture($utilisateurId, $ligne) {
        try {
            $titre = trim($ligne[1]);

            // Extraction de la référence du contrat
            preg_match('/"([A-Z0-9]+)"/', $titre, $matches);
            $referenceContrat = isset($matches[1]) ? trim($matches[1]) : null;

            // Vérifie si le contrat est lié
            $estLieContrat = $referenceContrat && $this->contratExiste($referenceContrat, $utilisateurId);

            // Nettoyage de la valeur de la date et tentative de conversion
            $dateFactureStr = trim($ligne[9]);

            // Vérifie si la valeur est vide
            if (empty($dateFactureStr)) {
                throw new Exception("Date de facture absente pour la ligne : " . json_encode($ligne));
            }

            // Essayer plusieurs formats
            $formats = ['d/m/Y', 'Y-m-d', 'd-m-Y'];
            $dateFacture = false;

            foreach ($formats as $format) {
                $dateFacture = \DateTime::createFromFormat($format, $dateFactureStr);
                if ($dateFacture !== false) {
                    break;
                }
            }

            // Si la conversion échoue, afficher la date erronée
            if (!$dateFacture) {
                throw new Exception("Date de facture invalide : '" . $dateFactureStr . "' - Formats testés : " . implode(', ', $formats));
            }

            // Vérifie si la facture existe déjà pour éviter les doublons
            if ($this->factureExiste($utilisateurId, $referenceContrat, $titre, $dateFacture->format('Y-m-d'))) {
                return;
            }

            // Insérer la facture dans la base de données
            $stmt = $this->pdo->prepare('
            INSERT INTO factures 
            (reference_contrat, utilisateur_id, titre, date_facture, est_lie_contrat)
            VALUES 
            (:reference_contrat, :utilisateur_id, :titre, :date_facture, :est_lie_contrat)
        ');

            $stmt->execute([
                ':reference_contrat' => $referenceContrat,
                ':utilisateur_id' => $utilisateurId,
                ':titre' => $titre,
                ':date_facture' => $dateFacture->format('Y-m-d'),
                ':est_lie_contrat' => $estLieContrat ? 1 : 0
            ]);

        } catch (\PDOException $e) {
            throw new Exception("Erreur PDO : " . $e->getMessage());
        } catch (\Exception $e) {
            throw new Exception("Erreur : " . $e->getMessage());
        }
    }



    /**
     * Vérifie si un type de box existe déjà avant l'insertion.
     */
    private function boxTypeExiste($denomination, $utilisateurId) {
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) FROM box_types 
            WHERE denomination = :denomination AND utilisateur_id = :utilisateur_id
        ');
        $stmt->execute([
            ':denomination' => $denomination,
            ':utilisateur_id' => $utilisateurId
        ]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Importe un type de box si il n'existe pas déjà.
     */
    public function importerBoxType($ligne, $utilisateurId) {
        try {
            $denomination = $this->normalizeString($ligne[1]);
            $prixTtc = floatval(str_replace(',', '.', $ligne[3]));

            if ($this->boxTypeExiste($denomination, $utilisateurId)) {
                return;
            }

            $stmt = $this->pdo->prepare('
                INSERT INTO box_types 
                (denomination, prix_ttc, utilisateur_id)
                VALUES 
                (:denomination, :prix_ttc, :utilisateur_id)
            ');

            $stmt->execute([
                ':denomination' => $denomination,
                ':prix_ttc' => $prixTtc,
                ':utilisateur_id' => $utilisateurId
            ]);
        } catch (\PDOException $e) {
            throw new Exception("Erreur PDO : " . $e->getMessage());
        }
    }

    /**
     * Vérifie si une location existe déjà avant l'insertion.
     */
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

    /**
     * Importe une location si elle n'existe pas déjà.
     */
    public function importerLocation($utilisateurId, $ligne) {
        try {
            $dateDebut = \DateTime::createFromFormat('d/m/Y', $ligne[11]) ?: null;
            $dateFin = \DateTime::createFromFormat('d/m/Y', $ligne[12]) ?: null;

            if (!$dateDebut) {
                throw new Exception("Date invalide pour 'date_debut' : " . $ligne[11]);
            }

            $boxTypeId = $this->getBoxTypeIdByReference($ligne[9], $utilisateurId);
            if (!$boxTypeId) {
                throw new Exception("Type de box non trouvé pour la référence : " . $ligne[9]);
            }

            $clientNom = trim($ligne[2] . ' ' . $ligne[3]);

            if ($this->locationExiste($utilisateurId, $ligne[1], $boxTypeId, $clientNom, $dateDebut->format('Y-m-d'), $dateFin ? $dateFin->format('Y-m-d') : null)) {
                return;
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
        }
    }

    public function importerRecapVente($utilisateurId, $ligne) {
        try {
            $dateVenteStr = trim($ligne[1]);

            // Vérifier si la valeur est vide
            if (empty($dateVenteStr)) {
                throw new Exception("Date de vente absente pour la ligne : " . json_encode($ligne));
            }

            // Essayer plusieurs formats
            $formats = ['d/m/Y', 'Y-m-d', 'd-m-Y'];
            $dateVente = false;

            foreach ($formats as $format) {
                $dateVente = \DateTime::createFromFormat($format, $dateVenteStr);
                if ($dateVente !== false) {
                    break;
                }
            }

            // Si la conversion échoue, afficher la date erronée
            if (!$dateVente) {
                throw new Exception("Date de vente invalide : '" . $dateVenteStr . "' - Formats testés : " . implode(', ', $formats));
            }

            // Nettoyage et conversion des montants (remplace les virgules par des points)
            $totalHt = floatval(str_replace(',', '.', $ligne[5]));
            $tva = floatval(str_replace(',', '.', $ligne[6]));
            $totalTtc = floatval(str_replace(',', '.', $ligne[7]));

            // Vérifie si la vente existe déjà
            if ($this->recapVenteExiste($utilisateurId, $dateVente->format('Y-m-d'), $totalHt, $tva, $totalTtc)) {
                return; // Si la vente existe, on la saute
            }

            // Insertion dans la base de données
            $stmt = $this->pdo->prepare('
        INSERT INTO recap_ventes 
        (utilisateur_id, date_vente, total_ht, tva, total_ttc)
        VALUES 
        (:utilisateur_id, :date_vente, :total_ht, :tva, :total_ttc)
    ');

            $stmt->execute([
                ':utilisateur_id' => $utilisateurId,
                ':date_vente' => $dateVente->format('Y-m-d'), // Format MySQL
                ':total_ht' => $totalHt,
                ':tva' => $tva,
                ':total_ttc' => $totalTtc
            ]);

        } catch (\PDOException $e) {
            throw new Exception("Erreur PDO : " . $e->getMessage());
        } catch (\Exception $e) {
            throw new Exception("Erreur : " . $e->getMessage());
        }
    }

    private function recapVenteExiste($utilisateurId, $dateVente, $totalHt, $tva, $totalTtc) {
        $stmt = $this->pdo->prepare('
        SELECT COUNT(*) FROM recap_ventes 
        WHERE utilisateur_id = :utilisateur_id 
        AND date_vente = :date_vente 
        AND total_ht = :total_ht 
        AND tva = :tva 
        AND total_ttc = :total_ttc
    ');
        $stmt->execute([
            ':utilisateur_id' => $utilisateurId,
            ':date_vente' => $dateVente,
            ':total_ht' => $totalHt,
            ':tva' => $tva,
            ':total_ttc' => $totalTtc
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

    public function contratExiste($referenceContrat, $utilisateurId) {
        if (!$referenceContrat) {
            return false;
        }

        $stmt = $this->pdo->prepare('
        SELECT COUNT(*) 
        FROM locations 
        WHERE reference_contrat = :reference_contrat 
        AND utilisateur_id = :utilisateur_id
        ');

        $stmt->execute([
            ':reference_contrat' => $referenceContrat,
            ':utilisateur_id' => $utilisateurId
        ]);

        return $stmt->fetchColumn() > 0;
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
