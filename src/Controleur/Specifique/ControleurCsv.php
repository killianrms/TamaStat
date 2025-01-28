<?php

namespace App\Controleur\Specifique;

use App\Configuration\ConnexionBD;
use Exception;
use PDO;

class ControleurCsv
{
    public function ajouterDonneesDepuisFichier($csvFile)
    {
        $fileName = $csvFile['name'];
        $fileTmpName = $csvFile['tmp_name'];
        $fileSize = $csvFile['size'];
        $fileType = $csvFile['type'];

        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($fileExt !== 'csv') {
            throw new Exception("Le fichier doit être au format CSV.");
        }

        $connexion = new ConnexionBD();
        $pdo = $connexion->getPdo();

        if (($handle = fopen($fileTmpName, 'r')) !== false) {
            fgetcsv($handle);

            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                // Extraction des données
                $reference = $data[0];
                $nomDeFamille = $data[1];
                $prenom = $data[2];
                $societe = $data[3];
                $mail = $data[4];
                $datePrelevement = $data[5];
                $centre = $data[6];
                $box = $data[7];
                $typeDeBox = $data[8];
                $prixHT = $data[9];
                $dateEntree = $data[10];
                $finDeLocation = $data[11];
                $sortieEffective = $data[12];
                $emailEnvoye = $data[13];
                $creerPar = $data[14];
                $etat = $data[15];

                $query = "INSERT INTO votre_table 
                    (reference, nom_de_famille, prenom, societe, mail, date_prelevement, centre, box, 
                    type_de_box, prix_ht, date_entree, fin_de_location, sortie_effective, email_envoye, creer_par, etat)
                    VALUES 
                    (:reference, :nomDeFamille, :prenom, :societe, :mail, :datePrelevement, :centre, :box, 
                    :typeDeBox, :prixHT, :dateEntree, :finDeLocation, :sortieEffective, :emailEnvoye, :creerPar, :etat)";

                $stmt = $pdo->prepare($query);
                $stmt->bindParam(':reference', $reference);
                $stmt->bindParam(':nomDeFamille', $nomDeFamille);
                $stmt->bindParam(':prenom', $prenom);
                $stmt->bindParam(':societe', $societe);
                $stmt->bindParam(':mail', $mail);
                $stmt->bindParam(':datePrelevement', $datePrelevement);
                $stmt->bindParam(':centre', $centre);
                $stmt->bindParam(':box', $box);
                $stmt->bindParam(':typeDeBox', $typeDeBox);
                $stmt->bindParam(':prixHT', $prixHT);
                $stmt->bindParam(':dateEntree', $dateEntree);
                $stmt->bindParam(':finDeLocation', $finDeLocation);
                $stmt->bindParam(':sortieEffective', $sortieEffective);
                $stmt->bindParam(':emailEnvoye', $emailEnvoye);
                $stmt->bindParam(':creerPar', $creerPar);
                $stmt->bindParam(':etat', $etat);

                $stmt->execute();
            }
            fclose($handle);
        } else {
            throw new Exception("Erreur lors de l'ouverture du fichier.");
        }

        $pdo = null;
    }
}
?>