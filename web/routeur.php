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

$stmt = $pdo->prepare('SELECT taille, nombre_box, prix_par_m3 FROM boxes_utilisateur WHERE utilisateur_id = :utilisateur_id');
$stmt->execute(['utilisateur_id' => $_SESSION['user']['id']]);
$boxes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcul des statistiques
$stats = [];
$revenuTotal = 0;
$capaciteTotale = 0;
$capaciteUtilisee = 0;

foreach ($boxes as $box) {
    $taille = $box['taille'];
    $locationsTaille = array_filter($locations, fn($loc) => $loc['nb_produits'] == $taille);

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

        <!-- Section Graphiques -->
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

    <!-- Données pour les graphiques -->
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

    <script src="../ressources/"></script>
<?php endif; ?>
</body>
</html>