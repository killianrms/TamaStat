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
$totalBoxesDispo = array_sum(array_column($boxes, 'nombre_box'));
$totalBoxesLoues = count($locations);
$tauxOccupationGlobal = ($totalBoxesDispo > 0) ? round(($totalBoxesLoues / $totalBoxesDispo) * 100, 2) : 0;

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

$tauxCapaciteUtilisee = ($capaciteTotale > 0) ? round(($capaciteUtilisee / $capaciteTotale) * 100, 2) : 0;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques</title>
    <link rel="stylesheet" href="../ressources/css/style.css">
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

        <div class="stats-globales">
            <div class="stat-card">
                <h3>Revenu total</h3>
                <div class="value"><?= number_format($revenuTotal, 2) ?> €</div>
            </div>

            <div class="stat-card">
                <h3>Taux d'occupation</h3>
                <div class="value"><?= $tauxOccupationGlobal ?>%</div>
                <small><?= $totalBoxesLoues ?>/<?= $totalBoxesDispo ?> boxes</small>
            </div>

            <div class="stat-card">
                <h3>Capacité utilisée</h3>
                <div class="value"><?= $tauxCapaciteUtilisee ?>%</div>
                <small><?= $capaciteUtilisee ?>m³/<?= $capaciteTotale ?>m³</small>
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

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctx, {
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
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.raw.toFixed(2) + ' €';
                            }
                        }
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
    </script>
<?php endif; ?>
</body>
</html>