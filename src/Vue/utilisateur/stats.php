<?php

use App\Configuration\ConnexionBD;
use App\Modele\CsvModele;

$connexion = new ConnexionBD();
$pdo = $connexion->getPdo();
$csvModele = new CsvModele();

$utilisateurId = $_SESSION['user']['id'];

// Récupérer les types de box de l'utilisateur
$boxTypes = $pdo->prepare('SELECT * FROM box_types WHERE utilisateur_id = ?');
$boxTypes->execute([$utilisateurId]);
$boxTypes = $boxTypes->fetchAll(PDO::FETCH_ASSOC);

// Récupérer le nombre de box disponibles par type
$utilisateurBoxes = $pdo->prepare('SELECT box_type_id, SUM(quantite) AS total FROM utilisateur_boxes WHERE utilisateur_id = ? GROUP BY box_type_id');
$utilisateurBoxes->execute([$utilisateurId]);
$boxDisponibles = $utilisateurBoxes->fetchAll(PDO::FETCH_KEY_PAIR);

// Récupérer toutes les locations actives
$locations = $pdo->prepare('SELECT * FROM locations WHERE utilisateur_id = ?');
$locations->execute([$utilisateurId]);
$locations = $locations->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les factures de l'utilisateur
$factures = $pdo->prepare('SELECT * FROM factures WHERE utilisateur_id = ?');
$factures->execute([$utilisateurId]);
$factures = $factures->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les ventes et remboursements de l'utilisateur pour calculer le CA
$query = 'SELECT * FROM recap_ventes WHERE utilisateur_id = ?';
$stmt = $pdo->prepare($query);
$stmt->execute([$utilisateurId]);
$recapVentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcul des revenus cumulés
$revenuTotal = 0;
$revenuMensuel = [];
$occupationParBox = [];
$capaciteTotale = 0;
$capaciteUtilisee = 0;

// Calculer le nombre de box libres, occupées et maximales
$boxLibres = [];
$boxMax = [];
$boxOccupees = [];
$boxLabels = [];
foreach ($boxTypes as $boxType) {
    $boxTypeId = $boxType['id'];

    // Nombre de box disponibles par type
    $totalBoxDispo = $boxDisponibles[$boxTypeId] ?? 0;

    if ($totalBoxDispo == 0) {
        continue;
    }

    // Nombre de box occupées
    $nbBoxLoues = count(array_filter($locations, fn($loc) => $loc['box_type_id'] == $boxTypeId));

    // Nombre de box libres
    $boxLibres[$boxTypeId] = $totalBoxDispo - $nbBoxLoues;

    // Stocker la quantité maximale
    $boxMax[$boxTypeId] = $totalBoxDispo;

    // Nombre de box occupées
    $boxOccupees[$boxTypeId] = $nbBoxLoues;

    $boxLabels[] = $boxType['denomination'];
}

// Lier les box à leurs prix
$boxTypesById = [];
foreach ($boxTypes as $boxType) {
    $boxTypesById[$boxType['id']] = $boxType;
}

// Calculer les taux d'occupation et revenus
foreach ($boxTypes as $boxType) {
    $boxTypeId = $boxType['id'];

    // Nombre de box disponibles
    $totalBoxDispo = $boxDisponibles[$boxTypeId] ?? 0;

    // Nombre de box actuellement loués (un box ne compte qu'une fois par mois)
    $nbBoxLoues = count(array_filter($locations, fn($loc) => $loc['box_type_id'] == $boxTypeId));

    // Nombre de box occupées
    $occupationParBox[$boxTypeId] = $nbBoxLoues;

    // Mise à jour des valeurs globales
    $capaciteTotale += $totalBoxDispo;
    $capaciteUtilisee += $nbBoxLoues;
}

// Calculer le revenu total et mensuel
foreach ($recapVentes as $vente) {
    $revenuTotal += $vente['total_ht'];
    $mois = date('Y-m', strtotime($vente['date_vente']));
    $revenuMensuel[$mois] = ($revenuMensuel[$mois] ?? 0) + $vente['total_ht'];
}

// Calculer le taux d'occupation global
$tauxOccupationGlobal = ($capaciteTotale > 0) ? min(100, round(($capaciteUtilisee / $capaciteTotale) * 100, 2)) : 0;

// Nouveaux contrats par mois
$nouveauxContratsParMois = [];
foreach ($locations as $location) {
    $mois = date('Y-m', strtotime($location['date_debut']));
    $nouveauxContratsParMois[$mois] = ($nouveauxContratsParMois[$mois] ?? 0) + 1;
}

$caMaxMensuel = 0;

$stmt = $pdo->prepare('
    SELECT bt.prix_ttc, ub.quantite 
    FROM utilisateur_boxes ub 
    INNER JOIN box_types bt ON ub.box_type_id = bt.id 
    WHERE ub.utilisateur_id = ? AND bt.utilisateur_id = ?
');
$stmt->execute([$utilisateurId, $utilisateurId]);
$boxData = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($boxData as $box) {
    $caMaxMensuel += $box['prix_ttc'] * $box['quantite'];
}

$moisActuel = date('Y-m');

$stmt = $pdo->prepare('
    SELECT SUM(total_ht) 
    FROM recap_ventes 
    WHERE utilisateur_id = ? 
    AND DATE_FORMAT(date_vente, "%Y-%m") = ?
');
$stmt->execute([$utilisateurId, $moisActuel]);
$caActuel = (float) $stmt->fetchColumn();


$caRestant = max(0, $caMaxMensuel - $caActuel);


// Récupérer le nombre total de box disponibles par type
$stmt = $pdo->prepare('SELECT SUM(quantite) FROM utilisateur_boxes WHERE utilisateur_id = ?');
$stmt->execute([$utilisateurId]);
$nbBoxTotal = (int) $stmt->fetchColumn();

// Récupérer le nombre de box actuellement louées
$stmt = $pdo->prepare('SELECT COUNT(*) FROM locations WHERE utilisateur_id = ?');
$stmt->execute([$utilisateurId]);
$nbBoxLouees = (int) $stmt->fetchColumn();

// Calcul du nombre de box restantes
$nbBoxRestantes = max(0, $nbBoxTotal - $nbBoxLouees);

// Calcul du taux d'occupation (si 0 box dispo, on met à 0 pour éviter division par 0)
$tauxOccupation = ($nbBoxTotal > 0) ? round(($nbBoxLouees / $nbBoxTotal) * 100, 2) : 0;

?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="stats-page">
<h1>Statistiques de vos locations</h1>

<!-- Statistiques globales -->
<div class="stats-globales">
    <div class="stat-card">
        <h3>Chiffre d'Affaires</h3>
        <div class="stat-content">
            <p><strong>CA Max Mensuel :</strong> <?= number_format($caMaxMensuel, 2) ?> €</p>
            <p><strong>CA Actuel :</strong> <?= number_format($caActuel, 2) ?> €</p>
            <p><strong>CA Restant :</strong> <?= number_format($caRestant, 2) ?> €</p>
        </div>
    </div>

    <div class="stat-card">
        <h3>Statistiques des Box</h3>
        <div class="stat-content">
            <p><strong>Nombre total de box :</strong> <?= $nbBoxTotal ?></p>
            <p><strong>Nombre de box louées :</strong> <?= $nbBoxLouees ?></p>
            <p><strong>Nombre de box restantes :</strong> <?= $nbBoxRestantes ?></p>
            <p><strong>Taux d'occupation :</strong> <?= $tauxOccupation ?> %</p>
        </div>
    </div>

</div>


<!-- 🛠️ CONTENEUR DES GRAPHIQUES -->
<div class="chart-container">
    <div class="chart-card">
        <h3>Chiffre d'affaires</h3>
        <div class="date-filters">
            <label for="startDateRevenue">Mois début :</label>
            <input type="month" id="startDateRevenue">

            <label for="endDateRevenue">Mois fin :</label>
            <input type="month" id="endDateRevenue">
        </div>
        <canvas id="revenuMensuelChart"></canvas>
    </div>

    <div class="chart-card">
        <h3>Nombre d'entrées</h3>
        <div class="date-filters">
            <label for="startDateEntrées">Mois début :</label>
            <input type="month" id="startDateEntrées">

            <label for="endDateEntrées">Mois fin :</label>
            <input type="month" id="endDateEntrées">
        </div>
        <canvas id="nouveauxContratsChart"></canvas>
    </div>

    <div class="chart-card">
        <h3>Quantité de Box</h3>
        <div class="dropdown">
            <button id="toggleFilter">🔽 Sélectionner les box</button>
            <div id="boxFilter" class="dropdown-content">
                <?php foreach ($boxLabels as $index => $boxLabel): ?>
                    <label>
                        <input type="checkbox" class="box-checkbox" value="<?= $index ?>" checked>
                        <?= htmlspecialchars($boxLabel) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <canvas id="boxLibreOccupeMaxChart"></canvas>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const moisLabels = <?= json_encode(array_keys($revenuMensuel)) ?>.reverse();
        const revenuMensuelData = <?= json_encode(array_values($revenuMensuel)) ?>.reverse();
        const moisContratsLabels = <?= json_encode(array_keys($nouveauxContratsParMois)) ?>.reverse();
        const nouveauxContratsData = <?= json_encode(array_values($nouveauxContratsParMois)) ?>.reverse();
        const boxLibresData = <?= json_encode(array_values($boxLibres)) ?>;
        const boxMaxData = <?= json_encode(array_values($boxMax)) ?>;
        const boxOccupeesData = <?= json_encode(array_values($boxOccupees)) ?>;
        const boxLabels = <?= json_encode($boxLabels) ?>;

        // 📊 Création du graphique CA
        const revenueCtx = document.getElementById('revenuMensuelChart').getContext('2d');
        const revenueChart = new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: moisLabels,
                datasets: [{
                    label: 'Évolution Mensuelle (€ HT)',
                    data: revenuMensuelData,
                    borderColor: '#0072bc',
                    tension: 0.1
                }]
            }
        });

        // 📊 Création du graphique des contrats
        const contratsCtx = document.getElementById('nouveauxContratsChart').getContext('2d');
        const contratsChart = new Chart(contratsCtx, {
            type: 'bar',
            data: {
                labels: moisContratsLabels,
                datasets: [{
                    label: 'Nombre d\'entrées mensuel',
                    data: nouveauxContratsData,
                    backgroundColor: '#ff6600'
                }]
            }
        });

        // 📊 Création du graphique de la quantité de box
        const boxCtx = document.getElementById("boxLibreOccupeMaxChart").getContext("2d");
        const boxChartData = {
            labels: boxLabels,
            datasets: [
                { label: "Libres", data: boxLibresData, backgroundColor: "#28a745" },
                { label: "Occupées", data: boxOccupeesData, backgroundColor: "#dc3545" },
                { label: "Maximales", data: boxMaxData, backgroundColor: "#007bff" }
            ]
        };
        const boxChart = new Chart(boxCtx, { type: "bar", data: boxChartData });

        // 🎯 Gestion des filtres DATE pour les graphiques temporels
        function updateChartWithDates(chart, labels, data, startInput, endInput) {
            const startDate = startInput.value || null;
            const endDate = endInput.value || null;

            let filteredLabels = [];
            let filteredData = [];

            labels.forEach((mois, index) => {
                if ((startDate === null || mois >= startDate) &&
                    (endDate === null || mois <= endDate)) {
                    filteredLabels.push(mois);
                    filteredData.push(data[index]);
                }
            });

            if (filteredLabels.length === 0) {
                alert("Aucune donnée à afficher pour cette période !");
                filteredLabels = labels;
                filteredData = data;
            }

            chart.data.labels = filteredLabels;
            chart.data.datasets[0].data = filteredData;
            chart.update();
        }

        // 🎯 Filtres pour Chiffre d'affaires
        document.getElementById('startDateRevenue').addEventListener('change', () => {
            updateChartWithDates(revenueChart, moisLabels, revenuMensuelData,
                document.getElementById('startDateRevenue'),
                document.getElementById('endDateRevenue'));
        });

        document.getElementById('endDateRevenue').addEventListener('change', () => {
            updateChartWithDates(revenueChart, moisLabels, revenuMensuelData,
                document.getElementById('startDateRevenue'),
                document.getElementById('endDateRevenue'));
        });

        // 🎯 Filtres pour Nombre d'entrées
        document.getElementById('startDateEntrées').addEventListener('change', () => {
            updateChartWithDates(contratsChart, moisContratsLabels, nouveauxContratsData,
                document.getElementById('startDateEntrées'),
                document.getElementById('endDateEntrées'));
        });

        document.getElementById('endDateEntrées').addEventListener('change', () => {
            updateChartWithDates(contratsChart, moisContratsLabels, nouveauxContratsData,
                document.getElementById('startDateEntrées'),
                document.getElementById('endDateEntrées'));
        });

        // 🎯 Gestion du sélecteur de box pour le graphique des box
        const toggleButton = document.getElementById("toggleFilter");
        const dropdownContent = document.getElementById("boxFilter");

        toggleButton.addEventListener("click", function (event) {
            event.stopPropagation();
            dropdownContent.classList.toggle("active");
        });

        document.querySelectorAll(".box-checkbox").forEach((checkbox, index) => {
            checkbox.addEventListener("change", function () {
                const selectedIndexes = Array.from(document.querySelectorAll(".box-checkbox:checked")).map(cb => parseInt(cb.value));

                boxChartData.labels = selectedIndexes.map(i => boxLabels[i]);
                boxChartData.datasets[0].data = selectedIndexes.map(i => boxLibresData[i]);
                boxChartData.datasets[1].data = selectedIndexes.map(i => boxOccupeesData[i]);
                boxChartData.datasets[2].data = selectedIndexes.map(i => boxMaxData[i]);

                boxChart.update();
            });
        });

        // Fermer le menu si clic en dehors
        document.addEventListener("click", function (event) {
            if (!toggleButton.contains(event.target) && !dropdownContent.contains(event.target)) {
                dropdownContent.classList.remove("active");
            }
        });
    });

</script>
</body>
</html>