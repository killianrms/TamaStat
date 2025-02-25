<?php
namespace App\Controleur\Specifique;

use App\Modele\CsvModele;
use Exception;

class ControleurCsv {
    private $csvModele;

    public function __construct() {
        $this->csvModele = new CsvModele();
    }

    // Importation des factures
    public function importerFactures($csvFile, $utilisateurId) {
        try {
            $fileExt = strtolower(pathinfo($csvFile['name'], PATHINFO_EXTENSION));
            if ($fileExt !== 'csv') {
                throw new Exception("Le fichier doit être au format CSV.");
            }

            $fileTmpName = $csvFile['tmp_name'];

            if (($handle = fopen($fileTmpName, 'r')) !== false) {
                stream_filter_append($handle, 'convert.iconv.ISO-8859-1/UTF-8');
                fgetcsv($handle); // Ignore la première ligne (en-têtes)

                while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                    if (count($data) >= 10) {
                        $this->csvModele->importerFacture($utilisateurId, $data);
                    }
                }

                fclose($handle);
            } else {
                throw new Exception("Erreur lors de l'ouverture du fichier.");
            }
        } catch (Exception $e) {
            // Nous n'enverrons pas de message d'erreur ou de succès ici
            echo json_encode(['status' => 'error']);
            exit;
        }

        // Pas de vérification ou message de confirmation, simplement l'importation.
        echo json_encode(['status' => 'success']);
        exit;
    }

    // Importation des types de box
    public function importerBoxTypes($csvFile) {
        try {
            $fileExt = strtolower(pathinfo($csvFile['name'], PATHINFO_EXTENSION));
            if ($fileExt !== 'csv') {
                throw new Exception("Le fichier doit être au format CSV.");
            }

            $fileTmpName = $csvFile['tmp_name'];

            if (($handle = fopen($fileTmpName, 'r')) !== false) {
                fgetcsv($handle); // Ignore la première ligne (en-têtes)

                while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                    if (count($data) >= 5) {
                        $utilisateurId = $_SESSION['user']['id'];
                        $this->csvModele->importerBoxType($data, $utilisateurId);
                    }
                }

                fclose($handle);
            } else {
                throw new Exception("Erreur lors de l'ouverture du fichier.");
            }
        } catch (Exception $e) {
            // Pas de message d'erreur ici
            echo json_encode(['status' => 'error']);
            exit;
        }

        // Pas de message de confirmation ici non plus, juste un signal de succès.
        echo json_encode(['status' => 'success']);
        exit;
    }

    // Importation des contrats
    public function importerContrats($csvFile, $utilisateurId) {
        try {
            $fileExt = strtolower(pathinfo($csvFile['name'], PATHINFO_EXTENSION));
            if ($fileExt !== 'csv') {
                throw new Exception("Le fichier doit être au format CSV.");
            }

            $fileTmpName = $csvFile['tmp_name'];

            if (($handle = fopen($fileTmpName, 'r')) !== false) {
                fgetcsv($handle); // Ignore la première ligne (en-têtes)

                while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                    if (count($data) >= 6) {
                        $this->csvModele->importerLocation($utilisateurId, $data);
                    }
                }

                fclose($handle);
            } else {
                throw new Exception("Erreur lors de l'ouverture du fichier.");
            }
        } catch (Exception $e) {
            // Pas de message d'erreur ici
            echo json_encode(['status' => 'error']);
            exit;
        }

        // Pas de message de confirmation ici non plus, juste un signal de succès.
        echo json_encode(['status' => 'success']);
        exit;
    }
}
?>