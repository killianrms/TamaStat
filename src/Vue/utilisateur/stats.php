<?php
USE App\Configuration\ConnexionBD;
USE App\Modele\CsvModele;

$connexion = new ConnexionBD();
$pdo = $connexion->getPdo();
$csvModele = new CsvModele();

$utilisateurId = $_SESSION['user']['id'];

// Récupérer les données des box et locations
$boxTypes = $pdo->prepare('SELECT id, denomination, prix_ttc, volume, quantite, actif FROM box_types WHERE utilisateur_id = ?');
$boxTypes->execute([$utilisateurId]);
$boxTypes = $boxTypes->fetchAll(PDO::FETCH_ASSOC);

$boxTypesById = [];
foreach ($boxTypes as $box) {
    $boxTypesById[$box['id']] = $box;
}

$locations = $pdo->prepare('SELECT * FROM locations WHERE utilisateur_id = ?');
$locations->execute([$utilisateurId]);
$locations = $locations->fetchAll(PDO::FETCH_ASSOC);

// Initialisation des statistiques
$stats = [
    'revenu_max' => 0,
    'revenu_total' => 0,
    'capacite_totale' => 0,
    'capacite_utilisee' => 0,
    'revenu_par_box' => [],
    'occupation_par_box' => [],
    'revenu_mensuel' => [],
    'capacite_mensuelle' => []
];

// Calcul des statistiques globales
foreach ($boxTypes as $box) {
    if ($box['actif']) {
        $stats['revenu_max'] += $box['prix_ttc'] * $box['quantite'];
        $stats['capacite_totale'] += $box['volume'] * $box['quantite'];
    }
    $stats['revenu_par_box'][$box['id']] = 0;
    $stats['occupation_par_box'][$box['id']] = 0;
}

$currentMonth = date('Y-m');
$stats['revenu_mensuel'][$currentMonth] = 0;
$stats['capacite_mensuelle'][$currentMonth] = 0;

// Calcul détaillé des statistiques
foreach ($locations as $location) {
    $boxTypeId = $location['box_type_id'];
    if (!isset($boxTypesById[$boxTypeId])) continue;

    $prixTTC = $boxTypesById[$boxTypeId]['prix_ttc'];
    $volume = $boxTypesById[$boxTypeId]['volume'];

    $start = new DateTime($location['date_debut']);
    $end = $location['date_fin'] ? new DateTime($location['date_fin']) : null;
    $current = clone $start;
    $now = new DateTime();

    while ($current <= ($end ?? $now)) {
        $mois = $current->format('Y-m');

        if (!isset($stats['revenu_mensuel'][$mois])) {
            $stats['revenu_mensuel'][$mois] = 0;
            $stats['capacite_mensuelle'][$mois] = 0;
        }
        $stats['revenu_mensuel'][$mois] += $prixTTC;
        $stats['capacite_mensuelle'][$mois] += $volume;

        $current->modify('+1 month');
    }

    $stats['revenu_total'] += $prixTTC;
    $stats['revenu_par_box'][$boxTypeId] += $prixTTC;
    $stats['occupation_par_box'][$boxTypeId]++;

    if ((!$end || $end >= new DateTime()) && $start <= new DateTime()) {
        $stats['capacite_utilisee'] += $volume;
    }
}

ksort($stats['revenu_mensuel']);
ksort($stats['capacite_mensuelle']);

$tauxOccupationGlobal = ($stats['capacite_totale'] > 0) ? round(($stats['capacite_utilisee'] / $stats['capacite_totale']) * 100, 2) : 0;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Statistiques</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<h1>Statistiques</h1>

<div class="stats-globales">
    <div class="stat-card"><h3>Revenu max théorique</h3><div class="value"><?= $stats['revenu_max'] ?> €/mois</div></div>
    <div class="stat-card"><h3>Revenu actuel</h3><div class="value"><?= $stats['revenu_mensuel'][date('Y-m')] ?? 0 ?> €/mois</div></div>
    <div class="stat-card"><h3>Capacité totale</h3><div class="value"><?= $stats['capacite_totale'] ?> m³</div></div>
    <div class="stat-card"><h3>Capacité utilisée</h3><div class="value"><?= $stats['capacite_utilisee'] ?> m³</div></div>
</div>

<div class="charts-grid">
    <canvas id="revenuMensuelChart"></canvas>
    <canvas id="capaciteChart"></canvas>
</div>

<script>
    new Chart(document.getElementById('revenuMensuelChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode(array_keys($stats['revenu_mensuel'])) ?>,
            datasets: [{
                label: 'Revenu mensuel (TTC)',
                data: <?= json_encode(array_values($stats['revenu_mensuel'])) ?>,
                borderColor: '#0072bc',
                fill: false
            }]
        }
    });

    new Chart(document.getElementById('capaciteChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode(array_keys($stats['capacite_mensuelle'])) ?>,
            datasets: [{
                label: 'Capacité utilisée (m³)',
                data: <?= json_encode(array_values($stats['capacite_mensuelle'])) ?>,
                borderColor: '#4CAF50',
                fill: false
            }]
        }
    });
</script>
</body>
</html>
