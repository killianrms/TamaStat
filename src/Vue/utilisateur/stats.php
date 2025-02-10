<?php
use App\Configuration\ConnexionBD;
use App\Modele\CsvModele;

$connexion = new ConnexionBD();
$pdo = $connexion->getPdo();
$csvModele = new CsvModele();

$utilisateurId = $_SESSION['user']['id'];

// Récupérer les données pour les statistiques
$boxTypes = $pdo->prepare('SELECT id, denomination, prix_ttc, quantite, actif FROM box_types WHERE utilisateur_id = ?');
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
    'locations_actives' => [],
    'revenu_mensuel' => [],
];

foreach ($boxTypes as $box) {
    if ($box['actif']) {
        $stats['revenu_max'] += $box['prix_ttc'] * $box['quantite'];
    }
}

// Calcul précis par mois
$currentMonth = date('Y-m');
$stats['revenu_mensuel'][$currentMonth] = 0;

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
        }

        $stats['revenu_mensuel'][$mois] += $boxTypesById[$location['box_type_id']]['prix_ttc'];

        $current->modify('+1 month');
    }
}

// Tri des mois
ksort($stats['revenu_mensuel']);
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
</div>

<!-- Graphiques -->
<div class="charts-grid">
    <div class="chart-card">
        <h3>Revenu mensuel</h3>
        <canvas id="revenuMensuelChart"></canvas>
    </div>
</div>

<script>
    const moisLabels = <?= json_encode(array_keys($stats['revenu_mensuel'])) ?>;
    const revenuMensuelData = <?= json_encode(array_values($stats['revenu_mensuel'])) ?>;

    // Graphique : Revenu mensuel
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
</script>
</body>
</html>
