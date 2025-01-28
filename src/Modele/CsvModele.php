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
        $stmt = $this->pdo->prepare('INSERT INTO locations (id, reference, nom_famille, prenom, societe, date_prelevement, centre, box, type_box, prix_ht, prix_ttc, date_entree, fin_location, sortie_effetive, email_envoye, cree_par, etat, commentaire) VALUES (:id, :reference, :nom_famille, :prenom, :societe, :date_prelevement, :centre, :box, :type_box, :prix_ht, :prix_ttc, :date_entree, :fin_location, :sortie_effetive, :email_envoye, :cree_par, :etat, :commentaire)');

        $stmt->execute([
            'id' => $ligne[0],
            'reference' => $ligne[1],
            'nom_famille' => $ligne[2],
            'prenom' => $ligne[3],
            'societe' => $ligne[4],
            'date_prelevement' => $ligne[5],
            'centre' => $ligne[6],
            'box' => $ligne[7],
            'type_box' => $ligne[8],
            'prix_ht' => $ligne[9],
            'prix_ttc' => $ligne[10],
            'date_entree' => $ligne[11],
            'fin_location' => $ligne[12],
            'sortie_effetive' => $ligne[13],
            'email_envoye' => $ligne[14],
            'cree_par' => $ligne[15],
            'etat' => $ligne[16],
            'commentaire' => $ligne[17]
        ]);
    }
}
?>
