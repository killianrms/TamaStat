<?php
use App\Configuration\ConnexionBD;
use App\Modele\CsvModele;

$connexion = new ConnexionBD();
$pdo = $connexion->getPdo();
$csvModele = new CsvModele();

$utilisateurId = $_SESSION['user']['id'];

// Récupérer les données pour les statistiques
$boxTypes = $pdo->prepare('SELECT id, denomination, prix_ttc, volume, quantite, actif FROM box_types WHERE utilisateur_id = ?');
$boxTypes->execute([$utilisateurId]);
$boxTypes = $boxTypes->fetchAll(PDO::FETCH_ASSOC);

$locations = $pdo->prepare('SELECT * FROM locations WHERE utilisateur_id = ?');
$locations->execute([$utilisateurId]);
$locations = $locations->fetchAll(PDO::FETCH_ASSOC);

// Créer un tableau indexé par l'ID du box type
$boxTypesById = [];
foreach ($boxTypes as $boxType) {
    $boxTypesById[$boxType['id']] = $boxType;
}

// Calcul des nouvelles statistiques
$stats = [
    'revenu_max' => 0,
    'capacite_totale' => 0,
    'locations_actives' => [],
    'revenu_mensuel' => [],
    'capacite_mensuelle' => 0,
    'capacite_utilisee' => 0
];

foreach ($boxTypes as $box) {
    if ($box['actif']) {
        $stats['revenu_max'] += $box['prix_ttc'] * $box['quantite'];
        $stats['capacite_totale'] += $box['volume'] * $box['quantite'];
    }
}

// Calcul précis par mois
$currentMonth = date('Y-m');
$stats['revenu_mensuel'][$currentMonth] = 0;
$stats['capacite_mensuelle'][$currentMonth] = 0;

foreach ($locations as $location) {
    $start = new DateTime($location['date_debut']);
    $end = $location['date_fin'] ? new DateTime($location['date_fin']) : null;

    // Trouver tous les mois concernés
    $current = clone $start;
    $now = new DateTime();

    while ($current <= ($end ?? $now)) {
        $mois = $current->format('Y-m');

        if (!isset($stats['revenu_mensuel'][$mois])) {
            $stats['revenu_mensuel'][$mois] = 0;
            $stats['capacite_mensuelle'][$mois] = 0;
        }

        $stats['revenu_mensuel'][$mois] += $boxTypesById[$location['box_type_id']]['prix_ttc'];
        $stats['capacite_mensuelle'][$mois] += $boxTypesById[$location['box_type_id']]['volume'];

        $current->modify('+1 month');
    }

    // Pour les stats actuelles
    if ((!$end || $end >= new DateTime()) && $start <= new DateTime()) {
        $stats['capacite_utilisee'] += $boxTypesById[$location['box_type_id']]['volume'];
    }
}

// Tri des mois
ksort($stats['revenu_mensuel']);
ksort($stats['capacite_mensuelle']);

$tauxOccupationGlobal = ($stats['capacite_totale'] > 0) ? round(($stats['capacite_utilisee'] / $stats['capacite_totale']) * 100, 2) : 0;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques</title>
    <link rel="stylesheet" href="../ressources/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="stats-page">
<h1>Statistiques de vos locations</h1>

<!-- Statistiques globales -->
<div class="stats-globales">
    <div class="stat-card">
        <h3>Revenu max théorique (TTC)</h3>
        <div class="value"><?= $stats['revenu_max'] ?> €/mois</div>
    </div>

    <div class="stat-card">
        <h3>Revenu actuel (TTC)</h3>
        <div class="value"><?= $stats['revenu_mensuel'][date('Y-m')] ?? 0 ?> €/mois</div>
    </div>

    <div class="stat-card">
        <h3>Capacité totale</h3>
        <div class="value"><?= $stats['capacite_totale'] ?> m³</div>
    </div>

    <div class="stat-card">
        <h3>Capacité utilisée</h3>
        <div class="value"><?= $stats['capacite_utilisee'] ?> m³</div>
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
        <h3>Capacité mensuelle</h3>
        <canvas id="capaciteChart"></canvas>
    </div>
</div>

<script>
    // Données pour les graphiques
    const boxLabels = <?= json_encode(array_column($boxTypes, 'denomination')) ?>;
    const revenuData = <?= json_encode(array_values($revenuParBox)) ?>;
    const occupationData = <?= json_encode(array_values($occupationParBox)) ?>;

    const moisLabels = <?= json_encode(array_keys($stats['revenu_mensuel'])) ?>;
    const revenuMensuelData = <?= json_encode(array_values($stats['revenu_mensuel'])) ?>;
    const capaciteMensuelleData = <?= json_encode(array_values($stats['capacite_mensuelle'])) ?>;

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
                label: 'Revenu mensuel (TTC)',
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

    // Graphique 4 : Capacité mensuelle
    new Chart(document.getElementById('capaciteChart'), {
        type: 'line',
        data: {
            labels: moisLabels,
            datasets: [{
                label: 'Capacité utilisée (m³)',
                data: capaciteMensuelleData,
                borderColor: '#4CAF50',
                fill: false
            }]
        },
        options: {
            responsive: true,
            plugins: {
                tooltip: { callbacks: { label: (ctx) => ctx.raw + ' m³' } }
            },
            scales: { y: { beginAtZero: true } }
        }
    });
</script>
</body>
</html>