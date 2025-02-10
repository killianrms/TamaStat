<?php
session_start();
require_once __DIR__ . '/../../Configuration/ConnexionBD.php';
require_once __DIR__ . '/../../Modele/CsvModele.php';

$connexion = new ConnexionBD();
$pdo = $connexion->getPdo();
$csvModele = new CsvModele();

$utilisateurId = $_SESSION['user']['id'];

// Récupérer les données pour les statistiques
$boxTypes = $pdo->prepare('SELECT * FROM box_types WHERE utilisateur_id = ?');
$boxTypes->execute([$utilisateurId]);
$boxTypes = $boxTypes->fetchAll(PDO::FETCH_ASSOC);

$locations = $pdo->prepare('SELECT * FROM locations WHERE utilisateur_id = ?');
$locations->execute([$utilisateurId]);
$locations = $locations->fetchAll(PDO::FETCH_ASSOC);

// Calculer les statistiques
$revenuTotal = 0;
$capaciteTotale = 0;
$capaciteUtilisee = 0;
$revenuParBox = [];
$occupationParBox = [];

foreach ($boxTypes as $boxType) {
    $boxTypeId = $boxType['id'];
    $locationsBox = array_filter($locations, fn($loc) => $loc['box_type_id'] == $boxTypeId);

    $revenuParBox[$boxTypeId] = count($locationsBox) * $boxType['prix_ttc'];
    $occupationParBox[$boxTypeId] = count($locationsBox);

    $revenuTotal += $revenuParBox[$boxTypeId];
    $capaciteTotale += $boxType['prix_ttc'];
    $capaciteUtilisee += $revenuParBox[$boxTypeId];
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

<!-- Filtres -->
<div class="filters">
    <label for="dateDebut">Date de début :</label>
    <input type="date" id="dateDebut" name="dateDebut">

    <label for="dateFin">Date de fin :</label>
    <input type="date" id="dateFin" name="dateFin">

    <label for="boxType">Type de box :</label>
    <select id="boxType" name="boxType">
        <option value="">Tous</option>
        <?php foreach ($boxTypes as $boxType): ?>
            <option value="<?= $boxType['id'] ?>"><?= $boxType['denomination'] ?></option>
        <?php endforeach; ?>
    </select>

    <button onclick="appliquerFiltres()">Appliquer les filtres</button>
</div>

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
        <h3>Répartition des locations</h3>
        <canvas id="repartitionChart"></canvas>
    </div>
</div>

<script>
    // Données pour les graphiques
    const boxLabels = <?= json_encode(array_column($boxTypes, 'denomination')) ?>;
    const revenuData = <?= json_encode(array_values($revenuParBox)) ?>;
    const occupationData = <?= json_encode(array_values($occupationParBox)) ?>;

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

    // Graphique 3 : Répartition des locations
    new Chart(document.getElementById('repartitionChart'), {
        type: 'pie',
        data: {
            labels: boxLabels,
            datasets: [{
                data: occupationData,
                backgroundColor: ['#0072bc', '#ff6600', '#2c3e50', '#4CAF50', '#9C27B0']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                tooltip: { callbacks: { label: (ctx) => ctx.raw + ' locations' } }
            }
        }
    });

    // Fonction pour appliquer les filtres
    function appliquerFiltres() {
        const dateDebut = document.getElementById('dateDebut').value;
        const dateFin = document.getElementById('dateFin').value;
        const boxType = document.getElementById('boxType').value;

        // Rediriger avec les filtres
        window.location.href = `routeur.php?route=stats&dateDebut=${dateDebut}&dateFin=${dateFin}&boxType=${boxType}`;
    }
</script>
</body>
</html>