<?php
use App\Configuration\ConnexionBD;
use App\Modele\CsvModele;

$connexion = new ConnexionBD();
$pdo = $connexion->getPdo();

$utilisateurId = $_SESSION['user']['id'];

// Récupérer la liste des parcs disponibles
$parcsQuery = $pdo->prepare('SELECT DISTINCT parc FROM factures WHERE utilisateur_id = ?');
$parcsQuery->execute([$utilisateurId]);
$parcs = $parcsQuery->fetchAll(PDO::FETCH_COLUMN);

// Gestion du filtre parc
$parcSelectionne = $_GET['parc'] ?? 'Tous';
$filtreParc = ($parcSelectionne !== 'Tous') ? "AND parc = :parc" : "";

// Requêtes avec filtre parc
$params = [':utilisateur_id' => $utilisateurId];
if($parcSelectionne !== 'Tous') $params[':parc'] = $parcSelectionne;

// 1. Revenu mensuel total (TTC et HT)
$revenuMensuel = $pdo->prepare("SELECT DATE_FORMAT(date_facture, '%Y-%m') AS mois, SUM(total_ttc) AS total_ttc, SUM(total_ht) AS total_ht FROM factures WHERE utilisateur_id = :utilisateur_id $filtreParc GROUP BY mois ORDER BY mois");
$revenuMensuel->execute($params);
$revenuMensuelData = $revenuMensuel->fetchAll(PDO::FETCH_ASSOC);

$moisLabels = array_column($revenuMensuelData, 'mois');
$revenuTTC = array_column($revenuMensuelData, 'total_ttc');
$revenuHT = array_column($revenuMensuelData, 'total_ht');

// 2. Nouveaux contrats mensuels
$nouveauxContrats = $pdo->prepare("SELECT DATE_FORMAT(date_debut, '%Y-%m') AS mois, COUNT(*) AS total FROM locations WHERE utilisateur_id = :utilisateur_id $filtreParc GROUP BY mois ORDER BY mois");
$nouveauxContrats->execute($params);
$nouveauxContratsData = $nouveauxContrats->fetchAll(PDO::FETCH_KEY_PAIR);

// 3. Statistiques des box
$totalBox = $pdo->prepare("SELECT bt.denomination, SUM(ub.quantite) AS total, COUNT(l.id) AS loues FROM utilisateur_boxes ub JOIN box_types bt ON ub.box_type_id = bt.id LEFT JOIN locations l ON ub.box_type_id = l.box_type_id AND l.utilisateur_id = :utilisateur_id $filtreParc WHERE ub.utilisateur_id = :utilisateur_id GROUP BY bt.id");
totalBox->execute([':utilisateur_id' => $utilisateurId]);
$boxStats = $totalBox->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE);

// 4. Taux d'occupation (en pourcentage)
$tauxOccupation = [];
foreach($boxStats as $type => $stats) {
    $taux = ($stats['total'] > 0) ? round(($stats['loues'] / $stats['total']) * 100, 2) : 0;
    $tauxOccupation[$type] = $taux;
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
<h1>Statistiques <?= $parcSelectionne !== 'Tous' ? " - $parcSelectionne" : '' ?></h1>

<div class="filters">
    <form method="GET">
        <label>Centre :
            <select name="parc" onchange="this.form.submit()">
                <option value="Tous">Tous les centres</option>
                <?php foreach($parcs as $parc): ?>
                    <option value="<?= htmlspecialchars($parc) ?>" <?= $parc === $parcSelectionne ? 'selected' : '' ?>>
                        <?= htmlspecialchars($parc) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </form>
</div>

<div class="stats-container">
    <div class="chart-card">
        <h3>Revenu mensuel (TTC & HT)</h3>
        <canvas id="revenuChart"></canvas>
    </div>

    <div class="chart-card">
        <h3>Nouveaux contrats mensuels</h3>
        <canvas id="contratsChart"></canvas>
    </div>

    <div class="chart-card">
        <h3>Occupation des box (%)</h3>
        <canvas id="occupationChart"></canvas>
    </div>
</div>

<script>
    new Chart(document.getElementById('revenuChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode($moisLabels) ?>,
            datasets: [
                {
                    label: 'Revenu TTC',
                    data: <?= json_encode($revenuTTC) ?>,
                    borderColor: '#4CAF50',
                    fill: false
                },
                {
                    label: 'Revenu HT',
                    data: <?= json_encode($revenuHT) ?>,
                    borderColor: '#FF9800',
                    fill: false
                }
            ]
        }
    });

    new Chart(document.getElementById('contratsChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_keys($nouveauxContratsData)) ?>,
            datasets: [{
                label: 'Nouveaux contrats',
                data: <?= json_encode(array_values($nouveauxContratsData)) ?>,
                backgroundColor: '#2196F3'
            }]
        }
    });

    new Chart(document.getElementById('occupationChart'), {
        type: 'pie',
        data: {
            labels: <?= json_encode(array_keys($tauxOccupation)) ?>,
            datasets: [{
                data: <?= json_encode(array_values($tauxOccupation)) ?>,
                backgroundColor: <?= json_encode(array_map(fn($t) => sprintf('#%06X', mt_rand(0, 0xFFFFFF)), array_keys($tauxOccupation))) ?>
            }]
        }
    });
</script>
</body>
</html>
