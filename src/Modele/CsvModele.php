<?php
namespace App\Modele;

use App\Configuration\ConnexionBD;
use PDO;

class CsvModele {
    private $pdo;

    public function __construct() {
        $connexion = new ConnexionBD();
        $this->pdo = $connexion->getPdo();
    }

    public function ajouterDonnees($ligne) {
        if (count($ligne) < 17) {
            throw new Exception("Ligne CSV incomplète.");
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO locations 
            (reference, nom_de_famille, prenom, societe, mail, date_prelevement, centre, box, type_de_box, 
            prix_ht, prix_ttc, date_entree, fin_location, sortie_effective, email_envoye, creer_par, etat)
            VALUES 
            (:reference, :nom_famille, :prenom, :societe, :mail, :date_prelevement, :centre, :box, 
            :type_box, :prix_ht, :prix_ttc, :date_entree, :fin_location, :sortie_effective, :email_envoye, :creer_par, :etat)'
        );

        $stmt->execute([
            'reference' => $ligne[0],
            'nom_famille' => $ligne[1],
            'prenom' => $ligne[2],
            'societe' => $ligne[3],
            'mail' => $ligne[4],
            'date_prelevement' => $ligne[5],
            'centre' => $ligne[6],
            'box' => $ligne[7],
            'type_box' => $ligne[8],
            'prix_ht' => $ligne[9],
            'prix_ttc' => $ligne[10],
            'date_entree' => $ligne[11],
            'fin_location' => $ligne[12],
            'sortie_effective' => $ligne[13],
            'email_envoye' => $ligne[14],
            'creer_par' => $ligne[15],
            'etat' => $ligne[16]
        ]);
    }
}
?>