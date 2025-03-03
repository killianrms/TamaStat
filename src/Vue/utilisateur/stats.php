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

// Calcul des revenus cumulés
$revenuTotal = 0;
$revenuMensuel = [];
$occupationParBox = [];
$capaciteTotale = 0;
$capaciteUtilisee = 0;

// Calculer le nombre de box libres et maximales
// Calculer le nombre de box libres, occupées et maximales
$boxLibres = [];
$boxMax = [];
$boxOccupees = [];
$boxLabels = [];
foreach ($boxTypes as $boxType) {
    $boxTypeId = $boxType['id'];

    // Nombre de box disponibles par type
    $totalBoxDispo = $boxDisponibles[$boxTypeId] ?? 0;

    if ($totalBoxDispo == 0) {
        continue;
    }

    // Nombre de box occupées
    $nbBoxLoues = count(array_filter($locations, fn($loc) => $loc['box_type_id'] == $boxTypeId));

    // Nombre de box libres
    $boxLibres[$boxTypeId] = $totalBoxDispo - $nbBoxLoues;

    // Stocker la quantité maximale
    $boxMax[$boxTypeId] = $totalBoxDispo;

    // Nombre de box occupées
    $boxOccupees[$boxTypeId] = $nbBoxLoues;

    $boxLabels[] = $boxType['denomination'];
}



// Lier les box à leurs prix
$boxTypesById = [];
foreach ($boxTypes as $boxType) {
    $boxTypesById[$boxType['id']] = $boxType;
}

// Calculer les taux d'occupation et revenus
foreach ($boxTypes as $boxType) {
    $boxTypeId = $boxType['id'];

    // Nombre de box disponibles
    $totalBoxDispo = $boxDisponibles[$boxTypeId] ?? 0;

    // Nombre de box actuellement loués (un box ne compte qu'une fois par mois)
    $nbBoxLoues = count(array_filter($locations, fn($loc) => $loc['box_type_id'] == $boxTypeId));

    // Nombre de box occupées
    $occupationParBox[$boxTypeId] = $nbBoxLoues;


    // Mise à jour des valeurs globales
    $capaciteTotale += $totalBoxDispo;
    $capaciteUtilisee += $nbBoxLoues;
}

// Calculer le revenu total et mensuel
foreach ($factures as $facture) {
    $revenuTotal += $facture['total_ht'];
    $mois = date('Y-m', strtotime($facture['date_facture']));
    $revenuMensuel[$mois] = ($revenuMensuel[$mois] ?? 0) + $facture['total_ht'];
}

// Calculer le taux d'occupation global
$tauxOccupationGlobal = ($capaciteTotale > 0) ? min(100, round(($capaciteUtilisee / $capaciteTotale) * 100, 2)) : 0;

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
    </style>
</head>
<body class="stats-page">
<h1>Statistiques de vos locations</h1>

<!-- Statistiques globales -->
<div class="stats-globales">
    <div class="stat-card">
        <h3>Revenu total (€ HT)</h3>
        <div class="value"><?= number_format($revenuTotal, 2) ?> €</div>
    </div>

    <div class="stat-card">
        <h3>Taux d'occupation</h3>
        <div class="value"><?= $tauxOccupationGlobal ?> %</div>
    </div>

    <div class="stat-card">
        <h3>Capacité utilisée</h3>
        <div class="value"><?= $capaciteUtilisee ?> box loués</div>
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

    <h3>Filtrer les types de box</h3>
    <div class="dropdown">
        <button id="toggleFilter">Sélectionner les box ▼</button>
        <div id="boxFilter" class="dropdown-content">
            <?php foreach ($boxLabels as $index => $boxLabel): ?>
                <label>
                    <input type="checkbox" class="box-checkbox" value="<?= $index ?>" checked>
                    <?= htmlspecialchars($boxLabel) ?>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="chart-card">
        <h3>Quantité de Box - Libre / Occupé / Max</h3>
        <canvas id="boxLibreOccupeMaxChart"></canvas>
    </div>

</div>

<script>
    const moisLabels = <?= json_encode(array_keys($revenuMensuel)) ?>.reverse();
    const revenuMensuelData = <?= json_encode(array_values($revenuMensuel)) ?>.reverse();
    const nouveauxContratsData = <?= json_encode(array_values($nouveauxContratsParMois)) ?>.reverse();
    const boxLibresData = <?= json_encode(array_values($boxLibres)) ?>;
    const boxMaxData = <?= json_encode(array_values($boxMax)) ?>;
    const boxOccupeesData = <?= json_encode(array_values($boxOccupees)) ?>;
    const boxLabels = <?= json_encode($boxLabels) ?>;

    document.addEventListener("DOMContentLoaded", function () {
        const ctx = document.getElementById("boxLibreOccupeMaxChart").getContext("2d");

        const boxLabels = <?= json_encode($boxLabels) ?>;
        const boxLibresData = <?= json_encode(array_values($boxLibres)) ?>;
        const boxOccupeesData = <?= json_encode(array_values($boxOccupees)) ?>;
        const boxMaxData = <?= json_encode(array_values($boxMax)) ?>;

        let chartData = {
            labels: boxLabels,
            datasets: [
                {
                    label: "Box Libres",
                    data: boxLibresData,
                    backgroundColor: "#28a745"
                },
                {
                    label: "Box Occupées",
                    data: boxOccupeesData,
                    backgroundColor: "#dc3545"
                },
                {
                    label: "Box Maximales",
                    data: boxMaxData,
                    backgroundColor: "#007bff"
                }
            ]
        };

        let chart = new Chart(ctx, {
            type: "bar",
            data: chartData
        });

        const toggleButton = document.getElementById("toggleFilter");
        const dropdownContent = document.getElementById("boxFilter");

        toggleButton.addEventListener("click", function () {
            dropdownContent.classList.toggle("active");
        });

        document.querySelectorAll(".box-checkbox").forEach((checkbox, index) => {
            checkbox.addEventListener("change", function () {
                let selectedIndexes = Array.from(document.querySelectorAll(".box-checkbox:checked")).map(cb => parseInt(cb.value));

                chartData.labels = selectedIndexes.map(i => boxLabels[i]);
                chartData.datasets[0].data = selectedIndexes.map(i => boxLibresData[i]);
                chartData.datasets[1].data = selectedIndexes.map(i => boxOccupeesData[i]);
                chartData.datasets[2].data = selectedIndexes.map(i => boxMaxData[i]);

                chart.update();
            });
        });

        document.addEventListener("click", function (event) {
            if (!toggleButton.contains(event.target) && !dropdownContent.contains(event.target)) {
                dropdownContent.classList.remove("active");
            }
        });
    });


    new Chart(document.getElementById('revenuMensuelChart'), {
        type: 'line',
        data: {
            labels: moisLabels,
            datasets: [{
                label: 'Évolution du Chiffre d\'affaire Mensuel (€ HT)',
                data: revenuMensuelData,
                borderColor: '#0072bc',
                tension: 0.1
            }]
        }
    });

    new Chart(document.getElementById('nouveauxContratsChart'), {
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
</script>
</body>
</html>