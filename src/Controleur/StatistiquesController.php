<?php
namespace App\Controller;

use App\Modele\StatistiquesModele;

class StatistiquesController {
    public function filtrerRevenuMensuel() {
        session_start();
        if (!isset($_SESSION['user']['id'])) {
            echo json_encode(['error' => 'Non authentifié']);
            exit;
        }

        $dateDebut = $_GET['dateDebut'] ?? null;
        $dateFin = $_GET['dateFin'] ?? null;

        $modele = new StatistiquesModele();
        $revenuFiltre = $modele->getRevenuMensuelFiltre(
            $_SESSION['user']['id'],
            $dateDebut,
            $dateFin
        );

        header('Content-Type: application/json');
        echo json_encode([
            'labels' => array_keys($revenuFiltre),
            'values' => array_values($revenuFiltre)
        ]);
    }
}
?>