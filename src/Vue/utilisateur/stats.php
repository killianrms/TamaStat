<?php
$conn = new mysqli('localhost', 'username', 'password', 'database_name');
$query = "SELECT colonne1, COUNT(*) AS total FROM votre_table GROUP BY colonne1";
$result = $conn->query($query);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

$conn->close();
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
    const chartData = <?php echo json_encode($data); ?>;

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
