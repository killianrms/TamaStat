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

// Récupérer les factures de l'utilisateur (utiliser prix HT au lieu de TTC)
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

    // Nombre de box actuellement loués
    $nbBoxLoues = count(array_filter($locations, fn($loc) => $loc['box_type_id'] == $boxTypeId));

    // Occupation par type de box (inclure même les box à 0%)
    $occupationParBox[$boxTypeId] = ($totalBoxDispo > 0) ? min(100, round(($nbBoxLoues / $totalBoxDispo) * 100, 2)) : 0;

    // Mise à jour des valeurs globales
    $capaciteTotale += $totalBoxDispo;
    $capaciteUtilisee += $nbBoxLoues;
}

// Calculer le revenu total et mensuel (Prix HT au lieu de TTC)
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
    <title>Statistiques</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .charts-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            flex-direction: row;
        }
    </style>
</head>
<body>
<h1>Statistiques de vos locations</h1>

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
    const moisLabels = <?= json_encode(array_keys($revenuMensuel)) ?>.reverse();
    const revenuMensuelData = <?= json_encode(array_values($revenuMensuel)) ?>.reverse();
    const nouveauxContratsData = <?= json_encode(array_values($nouveauxContratsParMois)) ?>.reverse();
    const boxLabels = <?= json_encode(array_column($boxTypes, 'denomination')) ?>.reverse();
    const occupationData = <?= json_encode(array_values($occupationParBox)) ?>.reverse();

    new Chart(document.getElementById('revenuMensuelChart'), {
        type: 'line',
        data: {
            labels: moisLabels,
            datasets: [{
                label: 'Revenu mensuel (€ HT)',
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