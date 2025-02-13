<?php
USE App\Configuration\ConnexionBD;
USE App\Modele\CsvModele;

$connexion = new ConnexionBD();
$pdo = $connexion->getPdo();
$csvModele = new CsvModele();

$utilisateurId = $_SESSION['user']['id'];

// Récupérer les données pour les statistiques
$boxTypes = $pdo->prepare('
    SELECT bt.*, ub.quantite 
    FROM box_types bt
    LEFT JOIN utilisateur_boxes ub ON bt.id = ub.box_type_id AND ub.utilisateur_id = ?
');
$boxTypes->execute([$utilisateurId]);
$boxTypes = $boxTypes->fetchAll(PDO::FETCH_ASSOC);

$boxTypesById = [];
foreach ($boxTypes as $boxType) {
    $boxTypesById[$boxType['id']] = $boxType;
}

$locations = $pdo->prepare('SELECT * FROM locations WHERE utilisateur_id = ?');
$locations->execute([$utilisateurId]);
$locations = $locations->fetchAll(PDO::FETCH_ASSOC);

$boxStatus = [];
foreach ($boxTypes as $boxType) {
    $totalLoue = 0;

    foreach ($locations as $location) {
        if ($location['box_type_id'] == $boxType['id']) {
            $facture = $pdo->prepare('
                SELECT MAX(date_facture) AS derniere_facture 
                FROM factures 
                WHERE reference_contrat = ? 
                AND utilisateur_id = ?
                AND date_facture >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
            ');
            $facture->execute([$location['reference_contrat'], $utilisateurId]);

            if ($facture->fetchColumn()) {
                $totalLoue++;
            }
        }
    }

    $boxStatus[$boxType['id']] = [
        'quantite' => $boxType['quantite'] ?? 0,
        'loue' => $totalLoue,
        'disponible' => ($boxType['quantite'] ?? 0) - $totalLoue
    ];
}

$factures = $pdo->prepare('SELECT * FROM factures WHERE utilisateur_id = ?');
$factures->execute([$utilisateurId]);
$factures = $factures->fetchAll(PDO::FETCH_ASSOC);

// Calculer les revenus HT, TVA et TTC
$revenuTotalHT = array_sum(array_column($factures, 'total_ht'));
$revenuTotalTVA = array_sum(array_column($factures, 'tva'));
$revenuTotalTTC = array_sum(array_column($factures, 'total_ttc'));

$statsGlobales = [
    'capacite_totale' => array_sum(array_column($boxTypes, 'quantite')),
    'total_loue' => array_sum(array_column($boxStatus, 'loue')),
    'taux_occupation' => 0
];

if ($statsGlobales['capacite_totale'] > 0) {
    $statsGlobales['taux_occupation'] =
        round(($statsGlobales['total_loue'] / $statsGlobales['capacite_totale']) * 100, 2);
}

// Calculer le nombre total de box disponibles
$totalBoxDisponibles = 0;
foreach ($boxTypes as $boxType) {
    $totalBoxDisponibles += $boxType['quantite'];
}

// Calculer le nombre de box loués
$totalBoxLoues = count($locations);

// Calculer le taux d'occupation
$tauxOccupationGlobal = ($totalBoxDisponibles > 0) ? round(($totalBoxLoues / $totalBoxDisponibles) * 100, 2) : 0;

// Calculer le revenu mensuel
$revenuMensuel = [];
$nouveauxContratsParMois = [];
foreach ($locations as $location) {
    $mois = date('Y-m', strtotime($location['date_debut']));
    $boxTypeId = $location['box_type_id'];

    $prixTTC = isset($boxTypesById[$boxTypeId]) ? $boxTypesById[$boxTypeId]['prix_ttc'] : 0;

    $revenuMensuel[$mois] = ($revenuMensuel[$mois] ?? 0) + $prixTTC;
    $nouveauxContratsParMois[$mois] = ($nouveauxContratsParMois[$mois] ?? 0) + 1;
}

// Préparer les données pour les graphiques
$boxLabels = array_column($boxTypes, 'denomination');
$revenuParBox = [];
$occupationParBox = [];
$maxBoxParType = [];

foreach ($boxTypes as $boxType) {
    $boxTypeId = $boxType['id'];
    $locationsBox = array_filter($locations, fn($loc) => $loc['box_type_id'] == $boxTypeId);

    $revenuParBox[$boxTypeId] = count($locationsBox) * $boxType['prix_ttc'];
    $occupationParBox[$boxTypeId] = count($locationsBox);
    $maxBoxParType[$boxTypeId] = $boxType['quantite'];
}
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
        <h3>Revenu total HT</h3>
        <div class="value"><?= $revenuTotalHT ?> €</div>
    </div>

    <div class="stat-card">
        <h3>Revenu total TVA</h3>
        <div class="value"><?= $revenuTotalTVA ?> €</div>
    </div>

    <div class="stat-card">
        <h3>Revenu total TTC</h3>
        <div class="value"><?= $revenuTotalTTC ?> €</div>
    </div>

    <div class="stat-card">
        <h3>Capacité totale</h3>
        <div class="value"><?= $statsGlobales['capacite_totale'] ?></div>
    </div>

    <div class="stat-card">
        <h3>Box loués</h3>
        <div class="value"><?= $statsGlobales['total_loue'] ?></div>
    </div>

    <div class="stat-card">
        <h3>Taux d'occupation</h3>
        <div class="value"><?= $statsGlobales['taux_occupation'] ?>%</div>
    </div>

    <div class="stat-card">
        <h3>Nombre total de locations</h3>
        <div class="value"><?= $totalBoxLoues ?></div>
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
        <h3>Nouveaux contrats par mois</h3>
        <canvas id="nouveauxContratsChart"></canvas>
    </div>
    <?php foreach ($boxTypes as $boxType): ?>
        <div class="chart-card">
            <h3><?= htmlspecialchars($boxType['denomination']) ?></h3>
            <canvas id="chart-<?= $boxType['id'] ?>"></canvas>
            <script>
                new Chart(document.getElementById('chart-<?= $boxType['id'] ?>'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Loué', 'Disponible'],
                        datasets: [{
                            data: [
                                <?= $boxStatus[$boxType['id']]['loue'] ?>,
                                <?= $boxStatus[$boxType['id']]['disponible'] ?>
                            ],
                            backgroundColor: ['#ff6600', '#0072bc']
                        }]
                    }
                });
            </script>
        </div>
    <?php endforeach; ?>
</div>

<script>
    // Données pour les graphiques
    const boxLabels = <?= json_encode($boxLabels) ?>;
    const revenuData = <?= json_encode(array_values($revenuParBox)) ?>;
    const occupationData = <?= json_encode(array_values($occupationParBox)) ?>;
    const maxBoxData = <?= json_encode(array_values($maxBoxParType)) ?>;

    const moisLabels = <?= json_encode(array_keys($revenuMensuel)) ?>;
    const revenuMensuelData = <?= json_encode(array_values($revenuMensuel)) ?>;
    const nouveauxContratsData = <?= json_encode(array_values($nouveauxContratsParMois)) ?>;

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
            datasets: [
                {
                    label: 'Box loués',
                    data: occupationData,
                    backgroundColor: '#ff6600',
                    borderColor: '#e65c00',
                    borderWidth: 2
                },
                {
                    label: 'Box disponibles',
                    data: maxBoxData,
                    backgroundColor: '#0072bc',
                    borderColor: '#005f9e',
                    borderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: true },
                tooltip: { callbacks: { label: (ctx) => ctx.raw + ' box' } }
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

    // Graphique 4 : Nouveaux contrats par mois
    new Chart(document.getElementById('nouveauxContratsChart'), {
        type: 'line',
        data: {
            labels: moisLabels,
            datasets: [{
                label: 'Nouveaux contrats',
                data: nouveauxContratsData,
                borderColor: '#ff6600',
                fill: false
            }]
        },
        options: {
            responsive: true,
            plugins: {
                tooltip: { callbacks: { label: (ctx) => ctx.raw + ' contrats' } }
            },
            scales: { y: { beginAtZero: true } }
        }
    });
</script>
</body>
</html>