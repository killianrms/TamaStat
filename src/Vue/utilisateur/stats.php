<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use App\Configuration\ConnexionBD;
use App\Modele\CsvModele;
use App\Controleur\Specifique\ControleurCsv;

$connexion = new ConnexionBD();
$pdo = $connexion->getPdo();
$csvModele = new CsvModele();

$locations = $csvModele->getLocationsByUser($_SESSION['user']['id']);
$hasCSV = !empty($locations);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $controleurCsv = new ControleurCsv();
    try {
        $controleurCsv->importerCsv($_FILES['csv_file'], $_SESSION['user']['id']);
        header('Location: routeur.php?route=stats');
        exit;
    } catch (Exception $e) {
        echo "<div class='error-message'>Erreur : " . $e->getMessage() . "</div>";
    }
}

$filterPeriod = $_GET['period'] ?? 'all';
$filterSizes = $_GET['sizes'] ?? [];

$stmt = $pdo->prepare('SELECT taille, nombre_box, prix_par_m3 FROM boxes_utilisateur WHERE utilisateur_id = :utilisateur_id');
$stmt->execute(['utilisateur_id' => $_SESSION['user']['id']]);
$boxes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$filteredLocations = $locations;
if ($filterPeriod !== 'all') {
    $now = new DateTime();
    $filteredLocations = array_filter($filteredLocations, function($location) use ($filterPeriod, $now) {
        $locationDate = new DateTime($location['date_location']);
        $interval = $now->diff($locationDate);
        switch ($filterPeriod) {
            case '1month': return $interval->m < 1 && $interval->y == 0;
            case '6months': return $interval->m < 6 && $interval->y == 0;
            case '1year': return $interval->y < 1;
            default: return true;
        }
    });
}

if (!empty($filterSizes)) {
    $filteredLocations = array_filter($filteredLocations, function($location) use ($filterSizes) {
        return in_array($location['nb_produits'], $filterSizes);
    });
}

$totalBoxesDispo = array_sum(array_column($boxes, 'nombre_box'));
$totalBoxesLoues = count($filteredLocations);
$tauxOccupationGlobal = ($totalBoxesDispo > 0) ? round(($totalBoxesLoues / $totalBoxesDispo) * 100, 2) : 0;

$stats = [];
foreach ($boxes as $box) {
    $taille = $box['taille'];
    $locationsTaille = array_filter($filteredLocations, fn($loc) => $loc['nb_produits'] == $taille);

    $stats[$taille] = [
        'total' => $box['nombre_box'],
        'loues' => count($locationsTaille),
        'revenu' => count($locationsTaille) * $taille * $box['prix_par_m3']
    ];
}

$revenuTotal = array_sum(array_column($stats, 'revenu'));
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques</title>
</head>
<body>
<h1>Statistiques de vos locations</h1>

<?php if (!$hasCSV): ?>
    <form action="routeur.php?route=stats" method="POST" enctype="multipart/form-data">
        <label for="csv_file">Importer un fichier CSV :</label>
        <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
        <button type="submit">Importer</button>
    </form>
<?php else: ?>
    <div class="stats-container">
        <a href="routeur.php?route=stats&reimport=1" class="button">Modifier CSV</a>

        <form method="GET" action="routeur.php?route=stats" class="filters-form">
            <div class="filter-group">
                <label for="period">Période :</label>
                <select id="period" name="period">
                    <option value="all" <?= $filterPeriod === 'all' ? 'selected' : '' ?>>Toutes périodes</option>
                    <option value="1month" <?= $filterPeriod === '1month' ? 'selected' : '' ?>>1 mois</option>
                    <option value="6months" <?= $filterPeriod === '6months' ? 'selected' : '' ?>>6 mois</option>
                    <option value="1year" <?= $filterPeriod === '1year' ? 'selected' : '' ?>>1 an</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="sizes">Tailles des boxes :</label>
                <select id="sizes" name="sizes[]" multiple>
                    <?php foreach ($boxes as $box): ?>
                        <option value="<?= $box['taille'] ?>" <?= in_array($box['taille'], $filterSizes) ? 'selected' : '' ?>>
                            <?= $box['taille'] ?> m³
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <button type="submit" class="button">Appliquer les filtres</button>
            </div>
        </form>

        <div class="stats-globales">
            <div class="stat-card">
                <h3>Revenu total</h3>
                <div class="value"><?= number_format($revenuTotal, 2) ?> €</div>
            </div>

            <div class="stat-card">
                <h3>Taux d'occupation</h3>
                <div class="value"><?= $tauxOccupationGlobal ?>%</div>
                <small><?= $totalBoxesLoues ?>/<?= $totalBoxesDispo ?> boxes occupées</small>
            </div>
        </div>

        <div class="chart-container">
            <canvas id="revenueChart"></canvas>
        </div>

        <table class="stats-table">
            <thead>
            <tr>
                <th>Taille (m³)</th>
                <th>Box disponibles</th>
                <th>Box loués</th>
                <th>Taux d'occupation</th>
                <th>Revenu</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($stats as $taille => $data): ?>
                <?php if ($data['total'] > 0): ?>
                    <tr>
                        <td><?= $taille ?></td>
                        <td><?= $data['total'] ?></td>
                        <td><?= $data['loues'] ?></td>
                        <td><?= round(($data['loues'] / $data['total']) * 100, 2) ?>%</td>
                        <td><?= number_format($data['revenu'], 2) ?> €</td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<script>
    function updateChart() {
        const selectedSizes = [...document.querySelectorAll('#sizes option:checked')]
            .map(option => option.value);

        const filteredStats = <?= json_encode($stats) ?>;
        const filteredData = selectedSizes.length > 0
            ? selectedSizes.reduce((acc, size) => {
                if(filteredStats[size]) acc[size] = filteredStats[size];
                return acc;
            }, {})
            : filteredStats;

        revenueChart.data.labels = Object.keys(filteredData);
        revenueChart.data.datasets[0].data = Object.values(filteredData).map(d => d.revenu);
        revenueChart.update();
    }

    const ctx = document.getElementById('revenueChart').getContext('2d');
    const revenueChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_keys($stats)) ?>,
            datasets: [{
                label: 'Revenu par taille (€)',
                data: <?= json_encode(array_column($stats, 'revenu')) ?>,
                backgroundColor: '#0072bc',
                borderColor: '#005f9e',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value + ' €';
                        }
                    }
                }
            }
        }
    });

    document.getElementById('sizes').addEventListener('change', updateChart);
    document.getElementById('period').addEventListener('change', () => {
        document.querySelector('form').submit();
    });
</script>
</body>
</html>