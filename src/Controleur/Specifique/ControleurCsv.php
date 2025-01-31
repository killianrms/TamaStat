<?php
namespace App\Controleur\Specifique;

use App\Modele\CsvModele;
use Exception;

class ControleurCsv
{
    public function ajouterDonneesDepuisFichier($csvFile)
    {
        $fileExt = strtolower(pathinfo($csvFile['name'], PATHINFO_EXTENSION));
        if ($fileExt !== 'csv') {
            throw new Exception("Le fichier doit être au format CSV.");
        }

        $fileName = $csvFile['name'];
        $fileTmpName = $csvFile['tmp_name'];

        if (($handle = fopen($fileTmpName, 'r')) !== false) {
            fgetcsv($handle);

            while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                if ($this->validerLigneCsv($data)) {
                    $csvModele = new CsvModele();
                    try {
                        $csvModele->ajouterDonnees($data);
                    } catch (Exception $e) {
                        echo "Erreur lors de l'ajout des données : " . $e->getMessage();
                    }
                } else {
                    echo "Ligne invalide détectée et ignorée : " . implode(", ", $data);
                }
            }

            fclose($handle);
        } else {
            throw new Exception("Erreur lors de l'ouverture du fichier.");
        }
    }

    private function validerLigneCsv($data)
    {
        return count($data) >= 17;
    }
}
?>