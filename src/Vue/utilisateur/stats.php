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
    $locationsBox = array_filter($locations, fn($loc) => $loc['box_type_id'] == $boxTypeId);
    $locationsBoxParMois = [];
    foreach ($locationsBox as $loc) {
        $mois = date('Y-m', strtotime($loc['date_debut']));
        $locationsBoxParMois[$mois] = true; // Un box ne compte qu'une fois par mois
    }
    $nbBoxLoues = count($locationsBoxParMois);

    // Occupation par type de box (inclure même les box à 0%)
    $occupationParBox[$boxTypeId] = ($totalBoxDispo > 0) ? min(100, round(($nbBoxLoues / $totalBoxDispo) * 100, 2)) : 0;

    // Mise à jour des valeurs globales
    $capaciteTotale += $totalBoxDispo;
    $capaciteUtilisee += $nbBoxLoues;
}

// Calculer le revenu total et mensuel
foreach ($factures as $facture) {
    $revenuTotal += $facture['total_ttc'];
    $mois = date('Y-m', strtotime($facture['date_facture']));
    $revenuMensuel[$mois] = ($revenuMensuel[$mois] ?? 0) + $facture['total_ttc'];
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
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="stats-page">
<h1>Statistiques de vos locations</h1>

<!-- Statistiques globales -->
<div class="stats-globales">
    <div class="stat-card">
        <h3>Revenu total</h3>
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
        <h3>Revenu mensuel</h3>
        <canvas id="revenuMensuelChart"></canvas>
    </div>

    <div class="chart-card">
        <h3>Nouveaux contrats par mois</h3>
        <canvas id="nouveauxContratsChart"></canvas>
    </div>

    <div class="chart-card">
        <h3>Occupation par type de box</h3>
        <canvas id="occupationChart"></canvas>
    </div>
</div>

<script>
    const moisLabels = <?= json_encode(array_keys($revenuMensuel)) ?>;
    const revenuMensuelData = <?= json_encode(array_values($revenuMensuel)) ?>;
    const nouveauxContratsData = <?= json_encode(array_values($nouveauxContratsParMois)) ?>;
    const boxLabels = <?= json_encode(array_column($boxTypes, 'denomination')) ?>;
    const occupationData = <?= json_encode(array_values($occupationParBox)) ?>;

    new Chart(document.getElementById('revenuMensuelChart'), {
        type: 'line',
        data: {
            labels: moisLabels,
            datasets: [{
                label: 'Revenu mensuel (€)',
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
                label: 'Nouveaux contrats',
                data: nouveauxContratsData,
                backgroundColor: '#ff6600'
            }]
        }
    });

    new Chart(document.getElementById('occupationChart'), {
        type: 'bar',
        data: {
            labels: boxLabels,
            datasets: [{
                label: 'Occupation (%)',
                data: occupationData,
                backgroundColor: '#36A2EB'
            }]
        }
    });
</script>
</body>
</html>