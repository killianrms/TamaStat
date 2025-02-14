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

// 1. Revenu mensuel total (toutes factures)
$revenuMensuel = $pdo->prepare("
    SELECT 
        DATE_FORMAT(date_facture, '%Y-%m') AS mois, 
        SUM(total_ttc) AS total 
    FROM factures 
    WHERE utilisateur_id = :utilisateur_id 
    $filtreParc
    GROUP BY mois
    ORDER BY mois
");
$revenuMensuel->execute($params);
$revenuMensuelData = $revenuMensuel->fetchAll(PDO::FETCH_KEY_PAIR);

// 2. Nouveaux contrats par mois
$nouveauxContrats = $pdo->prepare("
    SELECT 
        DATE_FORMAT(date_debut, '%Y-%m') AS mois, 
        COUNT(*) AS total 
    FROM locations 
    WHERE utilisateur_id = :utilisateur_id 
    $filtreParc
    GROUP BY mois
    ORDER BY mois
");
$nouveauxContrats->execute($params);
$nouveauxContratsData = $nouveauxContrats->fetchAll(PDO::FETCH_KEY_PAIR);

// 3. Statistiques des box
$totalBox = $pdo->prepare("
    SELECT 
        bt.denomination,
        SUM(ub.quantite) AS total,
        COUNT(l.id) AS loues
    FROM utilisateur_boxes ub
    JOIN box_types bt ON ub.box_type_id = bt.id
    LEFT JOIN locations l ON ub.box_type_id = l.box_type_id 
        AND l.utilisateur_id = :utilisateur_id
        $filtreParc
    WHERE ub.utilisateur_id = :utilisateur_id
    GROUP BY bt.id
");
$totalBox->execute([':utilisateur_id' => $utilisateurId]);
$boxStats = $totalBox->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_UNIQUE);

// 4. Taux d'occupation
$tauxOccupation = [];
foreach($boxStats as $type => $stats) {
    $taux = ($stats['total'] > 0) ? round(($stats['loues'] / $stats['total']) * 100, 2) : 0;
    $tauxOccupation[$type] = $taux;
}

// 5. Dernière date de paiement par contrat
$dernieresFactures = $pdo->prepare("
    SELECT 
        reference_contrat,
        MAX(date_facture) AS derniere_date
    FROM factures 
    WHERE utilisateur_id = :utilisateur_id 
        AND reference_contrat IS NOT NULL
    GROUP BY reference_contrat
");
$dernieresFactures->execute([':utilisateur_id' => $utilisateurId]);
$contratsActifs = $dernieresFactures->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .filters {
            margin: 20px 0;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 8px;
        }
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
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
        <h3>Revenu mensuel</h3>
        <canvas id="revenuChart"></canvas>
    </div>

    <div class="chart-card">
        <h3>Nouveaux contrats</h3>
        <canvas id="contratsChart"></canvas>
    </div>

    <div class="chart-card">
        <h3>Occupation des box</h3>
        <canvas id="occupationChart"></canvas>
    </div>

    <div class="chart-card">
        <h3>Disponibilité des box</h3>
        <canvas id="disponibiliteChart"></canvas>
    </div>
</div>

<script>
    // Revenu mensuel
    new Chart(document.getElementById('revenuChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode(array_keys($revenuMensuelData)) ?>,
            datasets: [{
                label: 'Revenu TTC',
                data: <?= json_encode(array_values($revenuMensuelData)) ?>,
                borderColor: '#4CAF50',
                tension: 0.1
            }]
        }
    });

    // Nouveaux contrats
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

    // Occupation des box
    new Chart(document.getElementById('occupationChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_keys($tauxOccupation)) ?>,
            datasets: [{
                data: <?= json_encode(array_values($tauxOccupation)) ?>,
                backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56']
            }]
        }
    });

    // Disponibilité des box
    new Chart(document.getElementById('disponibiliteChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_keys($boxStats)) ?>,
            datasets: [{
                label: 'Box loués',
                data: <?= json_encode(array_column($boxStats, 'loues')) ?>,
                backgroundColor: '#FF9800'
            }, {
                label: 'Box disponibles',
                data: <?= json_encode(array_map(function($s) { return $s['total'] - $s['loues']; }, $boxStats)) ?>,
                backgroundColor: '#8BC34A'
            }]
        },
        options: {
            scales: {
                x: { stacked: true },
                y: { stacked: true }
            }
        }
    });
</script>
</body>
</html>