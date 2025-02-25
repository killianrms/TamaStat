<?php
namespace App\Controleur\Specifique;

use App\Modele\CsvModele;
use Exception;

class ControleurCsv {
    private $csvModele;

    public function __construct() {
        $this->csvModele = new CsvModele();
    }

    public function importerFactures($csvFile, $utilisateurId) {
        try {
            $fileExt = strtolower(pathinfo($csvFile['name'], PATHINFO_EXTENSION));
            if ($fileExt !== 'csv') {
                throw new Exception("Le fichier doit être au format CSV.");
            }

            $fileTmpName = $csvFile['tmp_name'];

            if (($handle = fopen($fileTmpName, 'r')) !== false) {
                stream_filter_append($handle, 'convert.iconv.ISO-8859-1/UTF-8');
                fgetcsv($handle);

                while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                    if (count($data) >= 10) {
                        $this->csvModele->importerFacture($utilisateurId, $data);
                    }
                }

                fclose($handle);
                echo json_encode(['status' => 'success', 'message' => 'Fichier importé avec succès.']);
            } else {
                throw new Exception("Erreur lors de l'ouverture du fichier.");
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function importerBoxTypes($csvFile) {
        try {
            $fileExt = strtolower(pathinfo($csvFile['name'], PATHINFO_EXTENSION));
            if ($fileExt !== 'csv') {
                throw new Exception("Le fichier doit être au format CSV.");
            }

            $fileTmpName = $csvFile['tmp_name'];

            if (($handle = fopen($fileTmpName, 'r')) !== false) {
                fgetcsv($handle);

                while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                    if (count($data) >= 5) {
                        $utilisateurId = $_SESSION['user']['id'];
                        $this->csvModele->importerBoxType($data, $utilisateurId);
                    }
                }

                fclose($handle);
                echo json_encode(['status' => 'success', 'message' => 'Fichier importé avec succès.']);
            } else {
                throw new Exception("Erreur lors de l'ouverture du fichier.");
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function importerContrats($csvFile, $utilisateurId) {
        try {
            $fileExt = strtolower(pathinfo($csvFile['name'], PATHINFO_EXTENSION));
            if ($fileExt !== 'csv') {
                throw new Exception("Le fichier doit être au format CSV.");
            }

            $fileTmpName = $csvFile['tmp_name'];

            if (($handle = fopen($fileTmpName, 'r')) !== false) {
                fgetcsv($handle);

                while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                    if (count($data) >= 6) {
                        $this->csvModele->importerLocation($utilisateurId, $data);
                    }
                }

                fclose($handle);
                echo json_encode(['status' => 'success', 'message' => 'Fichier importé avec succès.']);
            } else {
                throw new Exception("Erreur lors de l'ouverture du fichier.");
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}

?>