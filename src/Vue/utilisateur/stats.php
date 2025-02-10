<?php

use App\Configuration\ConnexionBD;

$userId = $_SESSION['user']['id'];
$connexion = new ConnexionBD();
$pdo = $connexion->getPdo();

$dateDebut = $_GET['date_debut'] ?? date('Y-m-01');
$dateFin = $_GET['date_fin'] ?? date('Y-m-t');
$typeBox = $_GET['type_box'] ?? '';

$query = "SELECT DATE_FORMAT(l.date_location, '%Y-%m') AS mois, SUM(l.prix) AS revenu_actuel
          FROM locations l 
          JOIN boxes b ON l.box_id = b.id
          WHERE l.utilisateur_id = :userId 
          AND l.date_location BETWEEN :dateDebut AND :dateFin" . ($typeBox ? " AND b.type = :typeBox" : "") . "
          GROUP BY mois
          ORDER BY mois ASC";

$stmt = $pdo->prepare($query);
$stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
$stmt->bindParam(':dateDebut', $dateDebut);
$stmt->bindParam(':dateFin', $dateFin);
if ($typeBox) {
    $stmt->bindParam(':typeBox', $typeBox);
}
$stmt->execute();
$revenus = $stmt->fetchAll(PDO::FETCH_ASSOC);

$queryMax = "SELECT SUM(b.prix_max) AS revenu_max FROM boxes b WHERE b.utilisateur_id = :userId";
$stmtMax = $pdo->prepare($queryMax);
$stmtMax->bindParam(':userId', $userId, PDO::PARAM_INT);
$stmtMax->execute();
$revenuMax = $stmtMax->fetch(PDO::FETCH_ASSOC)['revenu_max'];

$queryEspace = "SELECT SUM(b.surface) AS total_surface, 
                        SUM(CASE WHEN l.id IS NOT NULL THEN b.surface ELSE 0 END) AS espace_occupe
                FROM boxes b 
                LEFT JOIN locations l ON b.id = l.box_id AND l.utilisateur_id = :userId
                WHERE b.utilisateur_id = :userId";
$stmtEspace = $pdo->prepare($queryEspace);
$stmtEspace->bindParam(':userId', $userId, PDO::PARAM_INT);
$stmtEspace->execute();
$espace = $stmtEspace->fetch(PDO::FETCH_ASSOC);
$espaceTotal = $espace['total_surface'];
$espaceOccupe = $espace['espace_occupe'];
$espaceDisponible = $espaceTotal - $espaceOccupe;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Statistiques</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<h1>Statistiques</h1>
<form method="GET">
    <label>Date début : <input type="date" name="date_debut" value="<?= $dateDebut ?>"></label>
    <label>Date fin : <input type="date" name="date_fin" value="<?= $dateFin ?>"></label>
    <label>Type de box :
        <select name="type_box">
            <option value="">Tous</option>
            <option value="small">Petit</option>
            <option value="medium">Moyen</option>
            <option value="large">Grand</option>
        </select>
    </label>
    <button type="submit">Filtrer</button>
</form>

<h2>Revenus</h2>
<canvas id="revenusChart"></canvas>

<h2>Espace disponible vs occupé</h2>
<canvas id="espaceChart"></canvas>

<script>
    const revenusData = {
        labels: [<?= implode(',', array_map(fn($r) => "'" . $r['mois'] . "'", $revenus)) ?>],
        datasets: [{
            label: 'Revenu Actuel',
            data: [<?= implode(',', array_map(fn($r) => $r['revenu_actuel'], $revenus)) ?>],
            backgroundColor: 'rgba(54, 162, 235, 0.5)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    };

    const espaceData = {
        labels: ['Espace Occupé', 'Espace Disponible'],
        datasets: [{
            label: 'Espace en m²',
            data: [<?= $espaceOccupe ?>, <?= $espaceDisponible ?>],
            backgroundColor: ['rgba(255, 99, 132, 0.5)', 'rgba(75, 192, 192, 0.5)'],
            borderColor: ['rgba(255, 99, 132, 1)', 'rgba(75, 192, 192, 1)'],
            borderWidth: 1
        }]
    };

    new Chart(document.getElementById('revenusChart'), { type: 'line', data: revenusData });
    new Chart(document.getElementById('espaceChart'), { type: 'doughnut', data: espaceData });
</script>
</body>
</html>
