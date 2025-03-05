<?php

use App\Configuration\ConnexionBD;
use App\Modele\CsvModele;

$connexion = new ConnexionBD();
$pdo = $connexion->getPdo();
$csvModele = new CsvModele();

$utilisateurId = $_SESSION['user']['id'];

// Récupérer les types de box de l'utilisateur
$boxTypes = $pdo->prepare('SELECT * FROM box_types WHERE utilisateur_id = ?');
$boxTypes->execute([$utilisateurId]);
$boxTypes = $boxTypes->fetchAll(PDO::FETCH_ASSOC);

// Récupérer le nombre de box disponibles par type
$utilisateurBoxes = $pdo->prepare('SELECT box_type_id, SUM(quantite) AS total FROM utilisateur_boxes WHERE utilisateur_id = ? GROUP BY box_type_id');
$utilisateurBoxes->execute([$utilisateurId]);
$boxDisponibles = $utilisateurBoxes->fetchAll(PDO::FETCH_KEY_PAIR);

// Récupérer toutes les locations actives
$locations = $pdo->prepare('SELECT * FROM locations WHERE utilisateur_id = ?');
$locations->execute([$utilisateurId]);
$locations = $locations->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les factures de l'utilisateur
$factures = $pdo->prepare('SELECT * FROM factures WHERE utilisateur_id = ?');
$factures->execute([$utilisateurId]);
$factures = $factures->fetchAll(PDO::FETCH_ASSOC);

// Récupérer le revenu total à partir de la table recap_vente
$revenuTotal = $pdo->prepare('SELECT SUM(total_ht) AS total FROM recap_ventes WHERE utilisateur_id = ?');
$revenuTotal->execute([$utilisateurId]);
$revenuTotal = $revenuTotal->fetchColumn();

// Récupérer les box de l'utilisateur avec leur quantité et leur prix
$utilisateurBoxes = $pdo->prepare('
    SELECT ub.box_type_id, ub.quantite, tb.prix_ttc 
    FROM utilisateur_boxes ub
    JOIN box_types tb ON ub.box_type_id = tb.id
    WHERE ub.utilisateur_id = ?
');
$utilisateurBoxes->execute([$utilisateurId]);
$utilisateurBoxes = $utilisateurBoxes->fetchAll(PDO::FETCH_ASSOC);

// Calculer le revenu max mensuel
$revenuMaxMensuel = 0;
foreach ($utilisateurBoxes as $box) {
    $revenuMaxMensuel += $box['quantite'] * $box['prix_ttc'];
}

// Récupérer les revenus mensuels à partir de la table recap_vente
$revenuMensuel = $pdo->prepare('
    SELECT DATE_FORMAT(date_vente, "%Y-%m") AS mois, SUM(total_ht) AS total 
    FROM recap_ventes 
    WHERE utilisateur_id = ?
    GROUP BY mois
    ORDER BY mois
');
$revenuMensuel->execute([$utilisateurId]);
$revenuMensuel = $revenuMensuel->fetchAll(PDO::FETCH_KEY_PAIR);

// Récupérer le nombre total de box de l'utilisateur
$totalBox = $pdo->prepare('
    SELECT SUM(quantite) AS total 
    FROM utilisateur_boxes 
    WHERE utilisateur_id = ?
');
$totalBox->execute([$utilisateurId]);
$totalBox = $totalBox->fetchColumn();

// Récupérer le nombre de box louées
$boxLouees = $pdo->prepare('
    SELECT COUNT(*) AS total 
    FROM locations 
    WHERE utilisateur_id = ? AND date_fin > NOW()
');
$boxLouees->execute([$utilisateurId]);
$boxLouees = $boxLouees->fetchColumn();

// Calculer le nombre de box restantes
$boxRestantes = $totalBox - $boxLouees;

// Calculer le taux d'occupation
$tauxOccupation = ($totalBox > 0) ? round(($boxLouees / $totalBox) * 100, 2) : 0;

// Nouveaux contrats par mois
$nouveauxContratsParMois = [];
foreach ($locations as $location) {
    $mois = date('Y-m', strtotime($location['date_debut']));
    $nouveauxContratsParMois[$mois] = ($nouveauxContratsParMois[$mois] ?? 0) + 1;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-globales {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stat-card h3 {
            margin: 0;
            font-size: 16px;
        }

        .stat-card .value {
            font-size: 24px;
            font-weight: bold;
            margin-top: 10px;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .chart-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .date-selector {
            margin-bottom: 10px;
            text-align: center;
        }

        .date-selector label {
            font-size: 14px;
            margin-right: 10px;
        }

        .date-selector input[type="month"] {
            padding: 5px;
            font-size: 14px;
            border-radius: 4px;
            border: 1px solid #ccc;
            margin-right: 10px;
        }
    </style>
</head>
<body class="stats-page">
<h1>Statistiques de vos locations</h1>

<!-- Statistiques globales -->
<div class="stats-globales">
    <!-- Sélecteur de date -->
    <div class="stat-card">
        <h3>Période</h3>
        <div class="date-selector">
            <label for="startDate">Date de début :</label>
            <input type="month" id="startDate" name="startDate">
            <label for="endDate">Date de fin :</label>
            <input type="month" id="endDate" name="endDate">
        </div>
    </div>

    <!-- Revenu max mensuel -->
    <div class="stat-card">
        <h3>Revenu max mensuel</h3>
        <div class="value" id="revenuMaxMensuel"><?= number_format($revenuMaxMensuel, 2) ?> €</div>
    </div>

    <!-- Revenu actuel -->
    <div class="stat-card">
        <h3>Revenu actuel</h3>
        <div class="value" id="revenuActuel">0 €</div>
    </div>

    <!-- Revenu restant -->
    <div class="stat-card">
        <h3>Revenu restant</h3>
        <div class="value" id="revenuRestant">0 €</div>
    </div>

    <!-- Nombre total de box -->
    <div class="stat-card">
        <h3>Nombre total de box</h3>
        <div class="value"><?= $totalBox ?></div>
    </div>

    <!-- Nombre de box louées -->
    <div class="stat-card">
        <h3>Box louées</h3>
        <div class="value" id="boxLouees"><?= $boxLouees ?></div>
    </div>

    <!-- Nombre de box restantes -->
    <div class="stat-card">
        <h3>Box restantes</h3>
        <div class="value" id="boxRestantes"><?= $boxRestantes ?></div>
    </div>

    <!-- Taux d'occupation -->
    <div class="stat-card">
        <h3>Taux d'occupation</h3>
        <div class="value" id="tauxOccupation"><?= $tauxOccupation ?> %</div>
    </div>
</div>

<!-- Graphiques -->
<div class="charts-grid">
    <div class="chart-card">
        <h3>Chiffre d'affaires</h3>
        <canvas id="revenuMensuelChart"></canvas>
    </div>

    <div class="chart-card">
        <h3>Nombre d'entrées</h3>
        <canvas id="nouveauxContratsChart"></canvas>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const moisLabels = <?= json_encode(array_keys($revenuMensuel)) ?>.reverse();
        const revenuMensuelData = <?= json_encode(array_values($revenuMensuel)) ?>.reverse();
        const nouveauxContratsData = <?= json_encode(array_values($nouveauxContratsParMois)) ?>.reverse();
        const revenuMaxMensuel = <?= json_encode($revenuMaxMensuel) ?>;
        const totalBox = <?= json_encode($totalBox) ?>;
        const boxLouees = <?= json_encode($boxLouees) ?>;
        const boxRestantes = <?= json_encode($boxRestantes) ?>;
        const tauxOccupation = <?= json_encode($tauxOccupation) ?>;

        // Graphique Chiffre d'affaires
        const revenuMensuelChart = new Chart(document.getElementById('revenuMensuelChart'), {
            type: 'line',
            data: {
                labels: moisLabels,
                datasets: [{
                    label: 'Évolution Mensuel (€ HT)',
                    data: revenuMensuelData,
                    borderColor: '#0072bc',
                    tension: 0.1
                }]
            }
        });

        // Graphique Nouveaux contrats
        const nouveauxContratsChart = new Chart(document.getElementById('nouveauxContratsChart'), {
            type: 'bar',
            data: {
                labels: moisLabels,
                datasets: [{
                    label: 'Nombre d\'entrées mensuel',
                    data: nouveauxContratsData,
                    backgroundColor: '#ff6600'
                }]
            }
        });

        // Sélecteurs de date
        const startDateInput = document.getElementById('startDate');
        const endDateInput = document.getElementById('endDate');
        const revenuActuelElement = document.getElementById('revenuActuel');
        const revenuRestantElement = document.getElementById('revenuRestant');
        const boxLoueesElement = document.getElementById('boxLouees');
        const boxRestantesElement = document.getElementById('boxRestantes');
        const tauxOccupationElement = document.getElementById('tauxOccupation');

        [startDateInput, endDateInput].forEach(input => {
            input.addEventListener('change', function () {
                const startDate = startDateInput.value;
                const endDate = endDateInput.value;

                if (startDate && endDate) {
                    // Calculer le nombre de mois entre les deux dates
                    const start = new Date(startDate + '-01');
                    const end = new Date(endDate + '-01');
                    const nbMois = (end.getFullYear() - start.getFullYear()) * 12 + (end.getMonth() - start.getMonth()) + 1;

                    // Calculer le revenu max pour la période
                    const revenuMaxPeriode = revenuMaxMensuel * nbMois;

                    // Calculer le revenu actuel pour la période
                    let revenuActuelPeriode = 0;
                    Object.keys(revenuMensuel).forEach(mois => {
                        const currentDate = new Date(mois + '-01');
                        if (currentDate >= start && currentDate <= end) {
                            revenuActuelPeriode += revenuMensuel[mois];
                        }
                    });

                    // Calculer le revenu restant
                    let revenuRestantPeriode = revenuMaxPeriode - revenuActuelPeriode;
                    if (revenuRestantPeriode < 0) {
                        revenuRestantPeriode = 0;
                    }

                    // Mettre à jour les éléments HTML pour les revenus
                    revenuActuelElement.textContent = revenuActuelPeriode.toFixed(2) + ' €';
                    revenuRestantElement.textContent = revenuRestantPeriode.toFixed(2) + ' €';

                    // Mettre à jour les éléments HTML pour les box
                    boxLoueesElement.textContent = boxLouees;
                    boxRestantesElement.textContent = boxRestantes;
                    tauxOccupationElement.textContent = tauxOccupation + ' %';
                }
            });
        });
    });
</script>
</body>
</html>