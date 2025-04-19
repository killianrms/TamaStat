<?php

namespace App\Modele;

use App\Configuration\ConnexionBD;
use Exception;
use PDO;

class CsvModele
{
    private $pdo;

    public function __construct()
    {
        $connexion = new ConnexionBD();
        $this->pdo = $connexion->getPdo();
    }

    /**
     * Vérifie si une facture existe déjà avant l'insertion.
     *
     * @param int $utilisateurId ID de l'utilisateur.
     * @param string|null $referenceContrat Référence du contrat.
     * @param string $titre Titre de la facture.
     * @param string $dateFacture Date de la facture (format Y-m-d).
     * @return bool Vrai si la facture existe, faux sinon.
     */
    private function factureExiste($utilisateurId, $referenceContrat, $titre, $dateFacture)
    {
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
     *
     * @param int $utilisateurId ID de l'utilisateur.
     * @param array $ligne Données de la ligne CSV.
     * @return void
     * @throws Exception Si une erreur survient lors de l'importation.
     */
    public function importerFacture($utilisateurId, $ligne)
    {
        try {
            $titre = trim($ligne[1]);
            preg_match('/"([A-Z0-9]+)"/', $titre, $matches);
            $referenceContrat = isset($matches[1]) ? trim($matches[1]) : null;
            $estLieContrat = $referenceContrat && $this->contratExiste($referenceContrat, $utilisateurId);
            $dateFactureStr = trim($ligne[9]);
            if (empty($dateFactureStr)) {
                throw new Exception("Date de facture absente pour la ligne : " . json_encode($ligne));
            }$formats = ['d/m/Y', 'Y-m-d', 'd-m-Y'];
            $dateFacture = false;

            foreach ($formats as $format) {
                $dateFacture = \DateTime::createFromFormat($format, $dateFactureStr);
                if ($dateFacture !== false) {
                    break;
                }
            }
            if (!$dateFacture) {
                throw new Exception("Date de facture invalide : '" . $dateFactureStr . "' - Formats testés : " . implode(', ', $formats));
            }
            if ($this->factureExiste($utilisateurId, $referenceContrat, $titre, $dateFacture->format('Y-m-d'))) {
                return;
            }
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
     *
     * @param string $denomination Dénomination du type de box.
     * @param int $utilisateurId ID de l'utilisateur.
     * @return bool Vrai si le type de box existe, faux sinon.
     */
    private function boxTypeExiste($denomination, $utilisateurId)
    {
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
     * Importe un type de box s'il n'existe pas déjà.
     *
     * @param array $ligne Données de la ligne CSV.
     * @param int $utilisateurId ID de l'utilisateur.
     * @return void
     * @throws Exception Si une erreur PDO survient.
     */
    public function importerBoxType($ligne, $utilisateurId)
    {
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
     *
     * @param int $utilisateurId ID de l'utilisateur.
     * @param string $referenceContrat Référence du contrat.
     * @param int $boxTypeId ID du type de box.
     * @param string $dateDebut Date de début (format Y-m-d).
     * @param string|null $dateFin Date de fin (format Y-m-d) ou null.
     * @return bool Vrai si la location existe, faux sinon.
     */
    private function locationExiste($utilisateurId, $referenceContrat, $boxTypeId, $dateDebut, $dateFin)
    {
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) FROM locations 
            WHERE utilisateur_id = :utilisateur_id
            AND reference_contrat = :reference_contrat
            AND box_type_id = :box_type_id
            AND date_debut = :date_debut
            AND (date_fin = :date_fin OR (date_fin IS NULL AND :date_fin IS NULL))
        ');
        $stmt->execute([
            ':utilisateur_id' => $utilisateurId,
            ':reference_contrat' => $referenceContrat,
            ':box_type_id' => $boxTypeId,
            ':date_debut' => $dateDebut,
            ':date_fin' => $dateFin
        ]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Importe une location (contrat) si elle n'existe pas déjà.
     *
     * @param int $utilisateurId ID de l'utilisateur.
     * @param array $ligne Données de la ligne CSV.
     * @return void
     * @throws Exception Si une erreur survient lors de l'importation.
     */
    public function importerLocation($utilisateurId, $ligne)
    {
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

            if ($this->locationExiste($utilisateurId, $ligne[1], $boxTypeId, $dateDebut->format('Y-m-d'), $dateFin ? $dateFin->format('Y-m-d') : null)) {
                return;
            }

            $stmt = $this->pdo->prepare('
                INSERT INTO locations 
                (reference_contrat, utilisateur_id, box_type_id, date_debut, date_fin)
                VALUES 
                (:reference_contrat, :utilisateur_id, :box_type_id, :date_debut, :date_fin)
            ');

            $stmt->execute([
                'reference_contrat' => $ligne[1],
                'utilisateur_id' => $utilisateurId,
                'box_type_id' => $boxTypeId,
                'date_debut' => $dateDebut->format('Y-m-d'),
                'date_fin' => $dateFin ? $dateFin->format('Y-m-d') : null
            ]);
        } catch (\PDOException $e) {
            throw new Exception("Erreur PDO : " . $e->getMessage());
        }
    }

    /**
     * Vérifie si un contrat clos existe déjà avant l'insertion.
     *
     * @param int $utilisateurId ID de l'utilisateur.
     * @param string $reference Référence du contrat.
     * @param \DateTime|null $dateEntree Date d'entrée.
     * @param \DateTime|null $sortieEffective Date de sortie effective.
     * @return bool Vrai si le contrat clos existe, faux sinon.
     */
    public function contratClosExiste($utilisateurId, $reference, $dateEntree, $sortieEffective) {
        $stmt = $this->pdo->prepare('
        SELECT COUNT(*) FROM contrats_clos 
        WHERE utilisateur_id = :utilisateur_id 
        AND reference = :reference 
        AND date_entree = :date_entree 
        AND sortie_effective = :sortie_effective
    ');

        $stmt->execute([
            'utilisateur_id' => $utilisateurId,
            'reference' => $reference,
            'date_entree' => $dateEntree ? $dateEntree->format('Y-m-d') : null,
            'sortie_effective' => $sortieEffective ? $sortieEffective->format('Y-m-d') : null
        ]);

        return $stmt->fetchColumn() > 0;
    }


    /**
     * Importe un contrat clos s'il n'existe pas déjà.
     *
     * @param int $utilisateurId ID de l'utilisateur.
     * @param array $ligne Données de la ligne CSV.
     * @return void
     * @throws Exception Si une erreur survient lors de l'importation.
     */
    public function importerContratClos($utilisateurId, $ligne)
    {
        try {
            $reference = trim($ligne[1]);
            $centre = trim($ligne[7]);

            $typeBox = trim($ligne[9]);
            $typeBox = mb_convert_encoding($typeBox, 'UTF-8', 'ISO-8859-1');

            $prixHtParts = explode(" ", trim($ligne[10]));
            $prixHt = floatval(str_replace(',', '.', $prixHtParts[0]));

            $dateEntree = !empty($ligne[11]) ? \DateTime::createFromFormat('d/m/Y', $ligne[11]) : null;
            $finLocation = !empty($ligne[12]) ? \DateTime::createFromFormat('d/m/Y', $ligne[12]) : null;
            $sortieEffective = !empty($ligne[13]) ? \DateTime::createFromFormat('d/m/Y', $ligne[13]) : null;

            $datesProbleme = [];
            if (!empty($ligne[11]) && !$dateEntree) $datesProbleme[] = "date_entree={$ligne[11]}";
            if (!empty($ligne[12]) && !$finLocation) $datesProbleme[] = "fin_location={$ligne[12]}";
            if (!empty($ligne[13]) && !$sortieEffective) $datesProbleme[] = "sortie_effective={$ligne[13]}";

            if (!empty($datesProbleme)) {
                throw new Exception("Erreur lors de la conversion des dates : " . implode(", ", $datesProbleme));
            }

            if ($this->contratClosExiste($utilisateurId, $reference, $dateEntree, $sortieEffective)) {
                return;
            }

            $stmt = $this->pdo->prepare('
        INSERT INTO contrats_clos 
        (reference, centre, type_box, prix_ht, date_entree, fin_location, sortie_effective, utilisateur_id)
        VALUES 
        (:reference, :centre, :type_box, :prix_ht, :date_entree, :fin_location, :sortie_effective, :utilisateur_id)
        ');

            $stmt->execute([
                'reference' => $reference,
                'centre' => $centre,
                'type_box' => $typeBox,
                'prix_ht' => $prixHt,
                'date_entree' => $dateEntree ? $dateEntree->format('Y-m-d') : null,
                'fin_location' => $finLocation ? $finLocation->format('Y-m-d') : null,
                'sortie_effective' => $sortieEffective ? $sortieEffective->format('Y-m-d') : null,
                'utilisateur_id' => $utilisateurId
            ]);

        } catch (Exception $e) {
            throw new Exception("Erreur lors de l'importation du contrat clos : " . $e->getMessage());
        }
    }



    public function importerRecapVente($utilisateurId, $ligne)
    {
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

    /**
     * Vérifie si un récapitulatif de vente existe déjà.
     *
     * @param int $utilisateurId ID de l'utilisateur.
     * @param string $dateVente Date de la vente (format Y-m-d).
     * @param float $totalHt Total hors taxes.
     * @param float $tva Montant de la TVA.
     * @param float $totalTtc Total toutes taxes comprises.
     * @return bool Vrai si le récapitulatif existe, faux sinon.
     */
    private function recapVenteExiste($utilisateurId, $dateVente, $totalHt, $tva, $totalTtc)
    {
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


    /**
     * Récupère l'ID d'un type de box par sa dénomination.
     *
     * @param string $denomination Dénomination du type de box.
     * @param int $utilisateurId ID de l'utilisateur.
     * @return int|null L'ID du type de box ou null s'il n'est pas trouvé.
     */
    public function getBoxTypeIdByReference($denomination, $utilisateurId)
    {
        $denomination = $this->normalizeString($denomination);

        $stmt = $this->pdo->prepare('SELECT id FROM box_types WHERE TRIM(denomination) = TRIM(:denomination) AND utilisateur_id = :utilisateur_id');
        $stmt->execute(['denomination' => $denomination, 'utilisateur_id' => $utilisateurId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['id'] : null;
    }

    /**
     * Vérifie si un contrat (location) existe pour une référence donnée.
     *
     * @param string|null $referenceContrat Référence du contrat.
     * @param int $utilisateurId ID de l'utilisateur.
     * @return bool Vrai si le contrat existe, faux sinon.
     */
    public function contratExiste($referenceContrat, $utilisateurId)
    {
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

    /**
     * Normalise une chaîne de caractères (encodage, espaces, caractères spéciaux).
     *
     * @param string $string La chaîne à normaliser.
     * @return string La chaîne normalisée.
     */
    private function normalizeString($string)
    {
        $string = trim($string);
        if (!mb_detect_encoding($string, 'UTF-8', true)) {
            $string = iconv('ISO-8859-1', 'UTF-8//TRANSLIT', $string);
        }
        $string = str_replace(["\xc2\xb0", "Â°"], "°", $string);
        $string = preg_replace('/\s+/', ' ', $string);
        return $string;
    }
}
