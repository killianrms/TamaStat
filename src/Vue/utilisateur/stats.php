<?php
USE App\Configuration\ConnexionBD;
USE App\Modele\CsvModele;

$connexion = new ConnexionBD();
$pdo = $connexion->getPdo();
$csvModele = new CsvModele();

$utilisateurId = $_SESSION['user']['id'];

// Récupérer les données pour les statistiques
$boxTypes = $pdo->prepare('SELECT * FROM box_types WHERE utilisateur_id = ?');
$boxTypes->execute([$utilisateurId]);
$boxTypes = $boxTypes->fetchAll(PDO::FETCH_ASSOC);

$boxTypesById = [];
foreach ($boxTypes as $boxType) {
    $boxTypesById[$boxType['id']] = $boxType;
}

$locations = $pdo->prepare('SELECT * FROM locations WHERE utilisateur_id = ?');
$locations->execute([$utilisateurId]);
$locations = $locations->fetchAll(PDO::FETCH_ASSOC);

// Calculer les statistiques
$revenuTotal = 0;
$capaciteTotale = 0;
$capaciteUtilisee = 0;
$revenuParBox = [];
$occupationParBox = [];
$revenuMensuel = [];
$locationsParMois = [];

foreach ($boxTypes as $boxType) {
    $boxTypeId = $boxType['id'];
    $locationsBox = array_filter($locations, fn($loc) => $loc['box_type_id'] == $boxTypeId);

    $revenuParBox[$boxTypeId] = count($locationsBox) * $boxType['prix_ttc'];
    $occupationParBox[$boxTypeId] = count($locationsBox);

    $revenuTotal += $revenuParBox[$boxTypeId];
    $capaciteTotale += $boxType['prix_ttc'];
    $capaciteUtilisee += $revenuParBox[$boxTypeId];
}

// Calculer le revenu mensuel
foreach ($locations as $location) {
    $mois = date('Y-m', strtotime($location['date_debut']));
    $boxTypeId = $location['box_type_id'];

    $prixTTC = isset($boxTypesById[$boxTypeId]) ? $boxTypesById[$boxTypeId]['prix_ttc'] : 0;

    $revenuMensuel[$mois] = ($revenuMensuel[$mois] ?? 0) + $prixTTC;
    $locationsParMois[$mois] = ($locationsParMois[$mois] ?? 0) + 1;
}

$tauxOccupationGlobal = ($capaciteTotale > 0) ? round(($capaciteUtilisee / $capaciteTotale) * 100, 2) : 0;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="stats-page">
<h1>Statistiques de vos locations</h1>

<!-- Statistiques globales -->
<div class="stats-globales">
    <div class="stat-card">
        <h3>Revenu total</h3>
        <div class="value"><?= $revenuTotal ?> €</div>
    </div>

    <div class="stat-card">
        <h3>Taux d'occupation</h3>
        <div class="value"><?= $tauxOccupationGlobal ?> %</div>
    </div>

    <div class="stat-card">
        <h3>Capacité utilisée</h3>
        <div class="value"><?= $capaciteUtilisee ?> m³</div>
    </div>

    <div class="stat-card">
        <h3>Nombre total de locations</h3>
        <div class="value"><?= count($locations) ?></div>
    </div>
</div>

<!-- Graphiques -->
<div class="charts-grid">
    <div class="chart-card">
        <h3>Revenu par type de box</h3>
        <canvas id="revenuChart"></canvas>
    </div>

    <div class="chart-card">
        <h3>Occupation par type de box</h3>
        <canvas id="occupationChart"></canvas>
    </div>

    <div class="chart-card">
        <h3>Revenu mensuel</h3>
        <canvas id="revenuMensuelChart"></canvas>
    </div>

    <div class="chart-card">
        <h3>Locations par mois</h3>
        <canvas id="locationsMensuellesChart"></canvas>
    </div>
</div>

<script>
    // Données pour les graphiques
    const boxLabels = <?= json_encode(array_column($boxTypes, 'denomination')) ?>;
    const revenuData = <?= json_encode(array_values($revenuParBox)) ?>;
    const occupationData = <?= json_encode(array_values($occupationParBox)) ?>;

    const moisLabels = <?= json_encode(array_keys($revenuMensuel)) ?>;
    const revenuMensuelData = <?= json_encode(array_values($revenuMensuel)) ?>;
    const locationsMensuellesData = <?= json_encode(array_values($locationsParMois)) ?>;

    // Graphique 1 : Revenu par type de box
    new Chart(document.getElementById('revenuChart'), {
        type: 'bar',
        data: {
            labels: boxLabels,
            datasets: [{
                label: 'Revenu (€)',
                data: revenuData,
                backgroundColor: '#0072bc',
                borderColor: '#005f9e',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: (ctx) => ctx.raw.toFixed(2) + ' €' } }
            },
            scales: { y: { beginAtZero: true } }
        }
    });

    // Graphique 2 : Occupation par type de box
    new Chart(document.getElementById('occupationChart'), {
        type: 'bar',
        data: {
            labels: boxLabels,
            datasets: [{
                label: 'Occupation',
                data: occupationData,
                backgroundColor: '#ff6600',
                borderColor: '#e65c00',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: (ctx) => ctx.raw + ' locations' } }
            },
            scales: { y: { beginAtZero: true } }
        }
    });

    // Graphique 3 : Revenu mensuel
    new Chart(document.getElementById('revenuMensuelChart'), {
        type: 'line',
        data: {
            labels: moisLabels,
            datasets: [{
                label: 'Revenu mensuel (€)',
                data: revenuMensuelData,
                borderColor: '#0072bc',
                fill: false
            }]
        },
        options: {
            responsive: true,
            plugins: {
                tooltip: { callbacks: { label: (ctx) => ctx.raw.toFixed(2) + ' €' } }
            },
            scales: { y: { beginAtZero: true } }
        }
    });

    // Graphique 4 : Locations par mois
    new Chart(document.getElementById('locationsMensuellesChart'), {
        type: 'line',
        data: {
            labels: moisLabels,
            datasets: [{
                label: 'Locations par mois',
                data: locationsMensuellesData,
                borderColor: '#ff6600',
                fill: false
            }]
        },
        options: {
            responsive: true,
            plugins: {
                tooltip: { callbacks: { label: (ctx) => ctx.raw + ' locations' } }
            },
            scales: { y: { beginAtZero: true } }
        }
    });
</script>
</body>
</html>