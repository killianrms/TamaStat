<?php
namespace App\Controleur\Specifique;

use App\Modele\CsvModele;
use App\Configuration\ConnexionBD;
use Exception;
use PDO;

class ControleurCsv {
    private $csvModele;
    private $pdo;

    public function __construct() {
        $this->csvModele = new CsvModele();
        $connexion = new ConnexionBD();
        $this->pdo = $connexion->getPdo();
    }


    /**
     * Importe les factures à partir d'un fichier CSV.
     *
     * @param array $csvFile Fichier CSV uploadé.
     * @param int $utilisateurId ID de l'utilisateur.
     * @return void
     */
    public function importerFactures($csvFile, $utilisateurId) {
        $fileExt = strtolower(pathinfo($csvFile['name'], PATHINFO_EXTENSION));
        if ($fileExt !== 'csv') {
            throw new Exception("Le fichier doit être au format CSV.");
        }

        $fileTmpName = $csvFile['tmp_name'];

        if (($handle = fopen($fileTmpName, 'r')) !== false) {
            stream_filter_append($handle, 'convert.iconv.ISO-8859-1/UTF-8');
            fgetcsv($handle); // Sauter l'en-tête

            while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                if (count($data) >= 10) {
                    $dateFactureStr = trim($data[9]);

                    if (empty($dateFactureStr)) {
                        continue;
                    }

                    $this->csvModele->importerFacture($utilisateurId, $data);
                }
            }
            fclose($handle);

            $stmt = $this->pdo->prepare('
            INSERT INTO import_tracking (utilisateur_id, table_name, date_dernier_import) 
            VALUES (?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE date_dernier_import = NOW()
        ');
            $stmt->execute([$utilisateurId, 'factures']);
        }
    }



    /**
     * Importe les types de box à partir d'un fichier CSV.
     *
     * @param array $csvFile Fichier CSV uploadé.
     * @return void
     */
    public function importerBoxTypes($csvFile) {
            $fileExt = strtolower(pathinfo($csvFile['name'], PATHINFO_EXTENSION));
            if ($fileExt !== 'csv') {
                throw new Exception("Le fichier doit être au format CSV.");
            }

            $fileTmpName = $csvFile['tmp_name'];

            if (($handle = fopen($fileTmpName, 'r')) === false) {
                throw new Exception("Erreur lors de l'ouverture du fichier.");
            }

            fgetcsv($handle);

            while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                if (count($data) >= 5) {
                    $utilisateurId = $_SESSION['user']['id'];
                    $this->csvModele->importerBoxType($data, $utilisateurId);
                }
            }
            fclose($handle);

        $stmt = $this->pdo->prepare('
    INSERT INTO import_tracking (utilisateur_id, table_name, date_dernier_import) 
    VALUES (?, ?, NOW()) 
    ON DUPLICATE KEY UPDATE date_dernier_import = NOW()
');
        $stmt->execute([$utilisateurId, 'box_types']);

    }

    public function importerRecapVentes($csvFile, $utilisateurId) {
        $fileExt = strtolower(pathinfo($csvFile['name'], PATHINFO_EXTENSION));
        if ($fileExt !== 'csv') {
            throw new Exception("Le fichier doit être au format CSV.");
        }

        $fileTmpName = $csvFile['tmp_name'];

        if (($handle = fopen($fileTmpName, 'r')) !== false) {
            stream_filter_append($handle, 'convert.iconv.ISO-8859-1/UTF-8');
            fgetcsv($handle); // Sauter l'en-tête

            while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                if (count($data) >= 8) {
                    $dateVenteStr = trim($data[1]);

                    if (empty($dateVenteStr)) {
                        continue;
                    }

                    $this->csvModele->importerRecapVente($utilisateurId, $data);
                }
            }
            fclose($handle);

            $stmt = $this->pdo->prepare('
            INSERT INTO import_tracking (utilisateur_id, table_name, date_dernier_import) 
            VALUES (?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE date_dernier_import = NOW()
        ');
            $stmt->execute([$utilisateurId, 'recap_ventes']);
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
            $fileExt = strtolower(pathinfo($csvFile['name'], PATHINFO_EXTENSION));
            if ($fileExt !== 'csv') {
                throw new Exception("Le fichier doit être au format CSV.");
            }

            $fileTmpName = $csvFile['tmp_name'];

            if (($handle = fopen($fileTmpName, 'r')) === false) {
                throw new Exception("Erreur lors de l'ouverture du fichier.");
            }

            fgetcsv($handle);

            while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                if (count($data) >= 6) {
                    $this->csvModele->importerLocation($utilisateurId, $data);
                }
            }
            fclose($handle);

        $stmt = $this->pdo->prepare('INSERT INTO import_tracking (utilisateur_id, table_name, date_dernier_import) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE date_dernier_import = NOW()');
        $stmt->execute([$utilisateurId, 'locations']);
    }


    public function importerContratsClos($csvFile, $utilisateurId) {
        $fileExt = strtolower(pathinfo($csvFile['name'], PATHINFO_EXTENSION));
        if ($fileExt !== 'csv') {
            throw new Exception("Le fichier doit être au format CSV.");
        }

        $fileTmpName = $csvFile['tmp_name'];

        if (($handle = fopen($fileTmpName, 'r')) === false) {
            throw new Exception("Erreur lors de l'ouverture du fichier.");
        }

        fgetcsv($handle); // Ignorer l'en-tête

        while (($data = fgetcsv($handle, 1000, ';')) !== false) {
            if (count($data) >= 7) {
                $this->csvModele->importerContratClos($utilisateurId, $data);
            }
        }
        fclose($handle);

        // Enregistrer l'import dans `import_tracking`
        $stmt = $this->pdo->prepare('INSERT INTO import_tracking (utilisateur_id, table_name, date_dernier_import) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE date_dernier_import = NOW()');
        $stmt->execute([$utilisateurId, 'contrats_clos']);
    }
}
?>