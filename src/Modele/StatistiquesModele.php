<?php
namespace App\Modele;

use App\Configuration\ConnexionBD;

class StatistiquesModele {
    private $pdo;

    public function __construct() {
        $connexion = new ConnexionBD();
        $this->pdo = $connexion->getPdo();
    }

    public function getRevenuMensuelFiltre($utilisateurId, $dateDebut, $dateFin) {
        $query = "
            SELECT DATE_FORMAT(date_facture, '%Y-%m') AS mois, SUM(total_ttc) AS revenu
            FROM factures
            WHERE utilisateur_id = :utilisateurId
            AND date_facture BETWEEN :dateDebut AND :dateFin
            GROUP BY mois
            ORDER BY mois
        ";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([
            ':utilisateurId' => $utilisateurId,
            ':dateDebut' => $dateDebut,
            ':dateFin' => $dateFin
        ]);

        return $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
    }
}
?>