<?php
namespace App\Controleur\Specifique;

use App\Modele\CsvModele;
use Exception;

class ControleurCsv {
    private $csvModele;

    public function __construct() {
        $this->csvModele = new CsvModele();
    }

    /**
     * Importe les factures à partir d'un fichier CSV.
     *
     * @param array $csvFile Fichier CSV uploadé.
     * @param int $utilisateurId ID de l'utilisateur.
     * @return void
     */
    public function importerFactures($csvFile, $utilisateurId) {
        try {
            $fileExt = strtolower(pathinfo($csvFile['name'], PATHINFO_EXTENSION));
            if ($fileExt !== 'csv') {
                throw new Exception("Le fichier doit être au format CSV.");
            }

            $fileTmpName = $csvFile['tmp_name'];
            if (($handle = fopen($fileTmpName, 'r')) === false) {
                throw new Exception("Erreur lors de l'ouverture du fichier.");
            }

            fgetcsv($handle);

            // Traitement des lignes
            while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                if (count($data) >= 10) {
                    $this->csvModele->importerFacture($utilisateurId, $data);
                }
            }

            fclose($handle);

            echo json_encode(['status' => 'success', 'message' => 'Factures importées avec succès.']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Importe les types de box à partir d'un fichier CSV.
     *
     * @param array $csvFile Fichier CSV uploadé.
     * @return void
     */
    public function importerBoxTypes($csvFile) {
        try {
            // Vérifie l'extension du fichier
            $fileExt = strtolower(pathinfo($csvFile['name'], PATHINFO_EXTENSION));
            if ($fileExt !== 'csv') {
                throw new Exception("Le fichier doit être au format CSV.");
            }

            // Ouvre le fichier CSV
            $fileTmpName = $csvFile['tmp_name'];
            if (($handle = fopen($fileTmpName, 'r')) === false) {
                throw new Exception("Erreur lors de l'ouverture du fichier.");
            }

            // Ignore la première ligne (en-têtes)
            fgetcsv($handle);

            // Traite chaque ligne du fichier
            while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                if (count($data) >= 5) {
                    $utilisateurId = $_SESSION['user']['id'];
                    $this->csvModele->importerBoxType($data, $utilisateurId);
                }
            }

            fclose($handle);

            // Retourne une réponse JSON en cas de succès
            echo json_encode(['status' => 'success', 'message' => 'Types de box importés avec succès.']);
        } catch (Exception $e) {
            // Retourne une réponse JSON en cas d'erreur
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Importe les contrats à partir d'un fichier CSV.
     *
     * @param array $csvFile Fichier CSV uploadé.
     * @param int $utilisateurId ID de l'utilisateur.
     * @return void
     */
    public function importerContrats($csvFile, $utilisateurId) {
        try {
            // Vérifie l'extension du fichier
            $fileExt = strtolower(pathinfo($csvFile['name'], PATHINFO_EXTENSION));
            if ($fileExt !== 'csv') {
                throw new Exception("Le fichier doit être au format CSV.");
            }

            // Ouvre le fichier CSV
            $fileTmpName = $csvFile['tmp_name'];
            if (($handle = fopen($fileTmpName, 'r')) === false) {
                throw new Exception("Erreur lors de l'ouverture du fichier.");
            }

            // Ignore la première ligne (en-têtes)
            fgetcsv($handle);

            // Traite chaque ligne du fichier
            while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                if (count($data) >= 6) {
                    $this->csvModele->importerLocation($utilisateurId, $data);
                }
            }

            fclose($handle);

            // Retourne une réponse JSON en cas de succès
            echo json_encode(['status' => 'success', 'message' => 'Contrats importés avec succès.']);
        } catch (Exception $e) {
            // Retourne une réponse JSON en cas d'erreur
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
?>