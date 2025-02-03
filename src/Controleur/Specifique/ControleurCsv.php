<?php
namespace App\Controleur\Specifique;

use App\Modele\CsvModele;
use Exception;

class ControleurCsv {
    public function importerCsv($csvFile, $utilisateur_id) {
        $fileExt = strtolower(pathinfo($csvFile['name'], PATHINFO_EXTENSION));
        if ($fileExt !== 'csv') {
            throw new Exception("Le fichier doit être au format CSV.");
        }

        $fileTmpName = $csvFile['tmp_name'];
        $csvModele = new CsvModele();

        if (($handle = fopen($fileTmpName, 'r')) !== false) {
            echo "Fichier CSV ouvert avec succès.<br>"; // Debug
            fgetcsv($handle);

            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                var_dump($data);
                if (count($data) >= 12) {
                    $csvModele->importerLocations($utilisateur_id, $data);
                }
            }

            fclose($handle);
        } else {
            throw new Exception("Erreur lors de l'ouverture du fichier.");
        }
    }
}
?>