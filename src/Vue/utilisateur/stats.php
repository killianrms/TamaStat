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

$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;
$selectedSizes = $_GET['sizes'] ?? [];

$locations = $csvModele->getLocationsByUser($_SESSION['user']['id']);
$hasCSV = !empty($locations);


$query = "SELECT * FROM locations WHERE utilisateur_id = :user_id";
$params = [':user_id' => $_SESSION['user']['id']];

if ($startDate && $endDate) {
    $query .= " AND date_location BETWEEN :start_date AND :end_date";
    $params[':start_date'] = $startDate;
    $params[':end_date'] = $endDate;
}

if (!empty($selectedSizes)) {
    $placeholders = implode(',', array_fill(0, count($selectedSizes), '?'));
    $query .= " AND nb_produits IN ($placeholders)";
    $params = array_merge($params, $selectedSizes);
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$filteredLocations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare('SELECT taille, nombre_box, prix_par_m3 FROM boxes_utilisateur WHERE utilisateur_id = :utilisateur_id');
$stmt->execute(['utilisateur_id' => $_SESSION['user']['id']]);
$boxes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stats = [];
$revenuTotal = 0;
$capaciteTotale = 0;
$capaciteUtilisee = 0;

foreach ($boxes as $box) {
    $taille = $box['taille'];
    $locationsTaille = array_filter($filteredLocations, fn($loc) => $loc['nb_produits'] == $taille);

    $stats[$taille] = [
        'total' => $box['nombre_box'],
        'loues' => count($locationsTaille),
        'revenu' => count($locationsTaille) * $taille * $box['prix_par_m3']
    ];

    $revenuTotal += $stats[$taille]['revenu'];
    $capaciteTotale += $box['nombre_box'] * $taille;
    $capaciteUtilisee += count($locationsTaille) * $taille;
}

$tauxOccupationGlobal = ($capaciteTotale > 0) ? round(($capaciteUtilisee / $capaciteTotale) * 100, 2) : 0;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques</title>
    <link rel="stylesheet" href="../ressources/css/style.css">
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
                    <input type="text" id="dateRange" name="date_range" placeholder="Sélectionner une période"
                           data-mode="range" data-alt-format="d/m/Y" data-date-format="Y-m-d">
                </div>

                <div class="filter-group">
                    <label>Tailles des boxes :</label>
                    <select name="sizes[]" multiple class="size-select">
                        <?php foreach ($boxes as $box): ?>
                            <option value="<?= $box['taille'] ?>"
                                <?= in_array($box['taille'], $selectedSizes) ? 'selected' : '' ?>>
                                <?= $box['taille'] ?> m³
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="button">Appliquer les filtres</button>
                <a href="routeur.php?route=stats" class="button reset-button">Réinitialiser</a>
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
    flatpickr("#dateRange", {
        mode: "range",
        dateFormat: "Y-m-d",
        locale: "fr",
        defaultDate: ["<?= $startDate ?>", "<?= $endDate ?>"]
    });

    document.querySelectorAll('.size-select').forEach(select => {
        new Choices(select, {
            removeItemButton: true,
            searchEnabled: true,
            placeholder: true,
            noResultsText: 'Aucun résultat'
        });
    });
</script>
</body>
</html>