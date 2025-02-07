<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use App\Configuration\ConnexionBD;
use App\Modele\CsvModele;

$connexion = new ConnexionBD();
$pdo = $connexion->getPdo();
$csvModele = new CsvModele();

$period = $_GET['period'] ?? 'all';
$selectedSizes = $_GET['sizes'] ?? [];

$now = new DateTime();
$startDate = null;
$endDate = null;

switch ($period) {
    case '6months':
        $startDate = (clone $now)->modify('-6 months')->format('Y-m-d');
        $endDate = $now->format('Y-m-d');
        break;
    case 'year':
        $startDate = (clone $now)->modify('-1 year')->format('Y-m-d');
        $endDate = $now->format('Y-m-d');
        break;
    case 'all':
    default:
        $startDate = '1970-01-01';
        $endDate = $now->format('Y-m-d');
        break;
}

$query = "SELECT * FROM locations WHERE utilisateur_id = :user_id AND date_location BETWEEN :start_date AND :end_date";
$params = [
    ':user_id' => $_SESSION['user']['id'],
    ':start_date' => $startDate,
    ':end_date' => $endDate
];

if (!empty($selectedSizes)) {
    $placeholders = implode(',', array_fill(0, count($selectedSizes), '?'));
    $query .= " AND nb_produits IN ($placeholders)";
    $params = array_merge($params, $selectedSizes);
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$filteredLocations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$locations = $csvModele->getLocationsByUser($_SESSION['user']['id']);
$hasCSV = !empty($locations);

$stmt = $pdo->prepare("
    SELECT ub.box_type_id, ub.quantite, bt.prix_ttc 
    FROM utilisateur_boxes ub
    JOIN box_types bt ON ub.box_type_id = bt.id
    WHERE ub.utilisateur_id = :utilisateur_id
");
$stmt->execute(['utilisateur_id' => $_SESSION['user']['id']]);
$boxes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stats = [];
$revenuTotal = 0;
$capaciteTotale = 0;
$capaciteUtilisee = 0;

foreach ($boxes as $box) {
    $boxTypeId = $box['box_type_id'];
    $locationsTaille = array_filter($filteredLocations, fn($loc) => $loc['nb_produits'] == $boxTypeId);

    $stats[$boxTypeId] = [
        'total' => $box['quantite'],
        'loues' => count($locationsTaille),
        'revenu' => count($locationsTaille) * $box['prix_ttc']
    ];

    $revenuTotal += $stats[$boxTypeId]['revenu'];
    $capaciteTotale += $box['quantite'];
    $capaciteUtilisee += count($locationsTaille);
}

$tauxOccupationGlobal = ($capaciteTotale > 0) ? round(($capaciteUtilisee / $capaciteTotale) * 100, 2) : 0;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
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

        <div class="filters-container">
            <form method="GET" action="routeur.php?route=stats">
                <div class="filter-group">
                    <label>Période :</label>
                    <div class="period-buttons">
                        <button type="button" class="period-btn" data-period="6months">6 derniers mois</button>
                        <button type="button" class="period-btn" data-period="year">Cette année</button>
                        <button type="button" class="period-btn" data-period="all">Total</button>
                    </div>
                    <input type="hidden" name="period" id="selectedPeriod" value="<?= $period ?>">
                </div>

                <div class="filter-group">
                    <label>Tailles des boxes :</label>
                    <div class="size-checkboxes">
                        <?php foreach ($boxes as $box): ?>
                            <label class="checkbox-label">
                                <input type="checkbox" name="sizes[]" value="<?= $box['box_type_id'] ?>"
                                    <?= in_array($box['box_type_id'], $selectedSizes) ? 'checked' : '' ?> >
                                <?= $box['box_type_id'] ?> m³
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button type="submit" class="button">Appliquer</button>
            </form>
        </div>

        <div class="charts-grid">
            <div class="chart-card">
                <h3>Revenu par taille de box</h3>
                <canvas id="revenueChart"></canvas>
            </div>

            <div class="chart-card">
                <h3>Taux d'occupation</h3>
                <canvas id="occupationChart"></canvas>
            </div>

            <div class="chart-card">
                <h3>Répartition des locations</h3>
                <canvas id="distributionChart"></canvas>
            </div>

            <div class="chart-card">
                <h3>Capacité utilisée</h3>
                <canvas id="capacityChart"></canvas>
            </div>
        </div>
    </div>

    <script>
        const statsData = {
            labels: <?= json_encode(array_keys($stats)) ?>,
            revenus: <?= json_encode(array_column($stats, 'revenu')) ?>,
            occupation: <?= json_encode(array_column($stats, 'loues')) ?>,
            totalBoxes: <?= json_encode(array_column($stats, 'total')) ?>,
            capaciteTotale: <?= $capaciteTotale ?>,
            capaciteUtilisee: <?= $capaciteUtilisee ?>
        };
    </script>

    <script src="../ressources/js/stats.js"></script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    document.querySelectorAll('.period-btn').forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('selectedPeriod').value = this.dataset.period;
            document.querySelector('form').submit();
        });
    });
</script>
</body>
</html>
