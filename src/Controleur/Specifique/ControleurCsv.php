<?php
namespace App\Controleur\Specifique;

use App\Modele\CsvModele;
use Exception;

class ControleurCsv
{
    public function ajouterDonneesDepuisFichier($csvFile)
    {
        $fileName = $csvFile['name'];
        $fileTmpName = $csvFile['tmp_name'];

        if (($handle = fopen($fileTmpName, 'r')) !== false) {
            fgetcsv($handle);

            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $csvModele = new CsvModele();
                $csvModele->ajouterDonnees($data);
            }
            fclose($handle);
        } else {
            throw new Exception("Erreur lors de l'ouverture du fichier.");
        }
    }
}
?>
