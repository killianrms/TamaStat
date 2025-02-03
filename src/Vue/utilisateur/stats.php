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
    var_dump($_FILES);
    $controleurCsv = new ControleurCsv();
    try {
        $controleurCsv->importerCsv($_FILES['csv_file'], $_SESSION['user']['id']);
        header('Location: routeur.php?route=stats');
        exit;
    } catch (Exception $e) {
        echo "<div class='error-message'>Erreur : " . $e->getMessage() . "</div>";
    }
}

// Récupérer les filtres
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
            case '1month':
                return $interval->m < 1 && $interval->y == 0;
            case '6months':
                return $interval->m < 6 && $interval->y == 0;
            case '1year':
                return $interval->y < 1;
            default:
                return true;
        }
    });
}

if (!empty($filterSizes)) {
    $filteredLocations = array_filter($filteredLocations, function($location) use ($filterSizes) {
        return in_array($location['nb_produits'], $filterSizes);
    });
}
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
            <label for="period">Période :</label>
            <select id="period" name="period">
                <option value="all" <?= $filterPeriod === 'all' ? 'selected' : '' ?>>Toutes les périodes</option>
                <option value="1month" <?= $filterPeriod === '1month' ? 'selected' : '' ?>>1 mois</option>
                <option value="6months" <?= $filterPeriod === '6months' ? 'selected' : '' ?>>6 mois</option>
                <option value="1year" <?= $filterPeriod === '1year' ? 'selected' : '' ?>>1 an</option>
            </select>

            <label for="sizes">Taille des boxes :</label>
            <select id="sizes" name="sizes[]" multiple>
                <?php foreach ($boxes as $box): ?>
                    <option value="<?= $box['taille'] ?>" <?= in_array($box['taille'], $filterSizes) ? 'selected' : '' ?>><?= $box['taille'] ?> m³</option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Filtrer</button>
        </form>

        <?php
        $stats = [];
        foreach ($boxes as $box) {
            $taille = $box['taille'];
            $totalBoxes = $box['nombre_box'];
            $prixParM3 = $box['prix_par_m3'];

            $locationsTaille = array_filter($filteredLocations, function($location) use ($taille) {
                return $location['nb_produits'] == $taille;
            });

            $stats[$taille] = [
                'total' => $totalBoxes,
                'loues' => count($locationsTaille),
                'revenu' => count($locationsTaille) * $taille * $prixParM3
            ];
        }
        ?>

        <canvas id="revenueChart" width="400" height="200"></canvas>
        <script>
            const ctx = document.getElementById('revenueChart').getContext('2d');
            const revenueChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode(array_keys($stats)) ?>,
                    datasets: [{
                        label: 'Revenu (€)',
                        data: <?= json_encode(array_column($stats, 'revenu')) ?>,
                        backgroundColor: 'rgba(0, 114, 188, 0.2)',
                        borderColor: 'rgba(0, 114, 188, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        </script>

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
                <?php if ($data['total'] > 0):?>
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
</body>
</html>