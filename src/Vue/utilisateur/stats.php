<?php
use App\Configuration\ConnexionBD;
use App\Configuration\ConfigurationBaseDeDonnees;

$connexion = new ConnexionBD();
$pdo = $connexion->getPdo();

$sql = "SELECT colonne1, SUM(colonne2) AS total FROM table_exemple GROUP BY colonne1";
$stmt = $pdo->query($sql);

$data = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $data[] = $row;
}

$dataJson = json_encode($data);
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
<h1>Statistiques</h1>
<canvas id="myChart"></canvas>
<script>
    const ctx = document.getElementById('myChart').getContext('2d');
    const chartData = <?php echo $dataJson; ?>;

    const labels = chartData.map(item => item.colonne1);
    const values = chartData.map(item => item.total);

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Total par catégorie',
                data: values,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>
</body>
</html>
