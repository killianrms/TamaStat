<?php
namespace App\Controleur\Specifique;

use App\Modele\CsvModele;
use Exception;

class ControleurCsv {
    public function importerCsv($csvFile, $utilisateur_id) {
        die("Importation appelée");
        $fileExt = strtolower(pathinfo($csvFile['name'], PATHINFO_EXTENSION));
        if ($fileExt !== 'csv') {
            throw new Exception("Le fichier doit être au format CSV.");
        }

        $fileTmpName = $csvFile['tmp_name'];
        $csvModele = new CsvModele();

        if (($handle = fopen($fileTmpName, 'r')) !== false) {
            fgetcsv($handle); // Ignorer l'en-tête

            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                if (count($data) >= 12) { // Validation minimale
                    $csvModele->importerLocations($utilisateur_id, $data);
                }
                var_dump($data);
                exit;
            }

            fclose($handle);
        } else {
            throw new Exception("Erreur lors de l'ouverture du fichier.");
        }
    }
}
?>