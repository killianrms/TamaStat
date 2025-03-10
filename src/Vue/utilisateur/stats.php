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

// Récupérer le mois et l'année actuels (ex: 2025-03)
$moisActuel = date('Y-m');

// Fonction pour récupérer le CA pour un mois donné
function getCAParMois($pdo, $utilisateurId, $mois) {
    $stmt = $pdo->prepare('
        SELECT SUM(total_ht) 
        FROM recap_ventes 
        WHERE utilisateur_id = ? 
        AND DATE_FORMAT(date_vente, "%Y-%m") = ?
    ');
    $stmt->execute([$utilisateurId, $mois]);
    return (float) $stmt->fetchColumn();
}

// Récupérer le CA pour le mois actuel au chargement
$caActuel = getCAParMois($pdo, $utilisateurId, $moisActuel);

// Calcul du CA max mensuel
$stmt = $pdo->prepare('
    SELECT bt.prix_ttc, ub.quantite 
    FROM utilisateur_boxes ub 
    INNER JOIN box_types bt ON ub.box_type_id = bt.id 
    WHERE ub.utilisateur_id = ? AND bt.utilisateur_id = ?
');
$stmt->execute([$utilisateurId, $utilisateurId]);
$caMaxMensuel = 0;

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $box) {
    $caMaxMensuel += $box['prix_ttc'] * $box['quantite'];
}

// Calcul du CA restant
$caRestant = max(0, $caMaxMensuel - $caActuel);


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
    <style>
        /* Style global de la page des statistiques */
        body.stats-page {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
            text-align: center;
        }

        h1 {
            color: #333;
            margin-bottom: 20px;
        }

        /* Conteneur des statistiques globales */
        .stats-globales {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        /* Cartes des statistiques */
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 300px;
            transition: transform 0.2s ease-in-out;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card h3 {
            color: #007bff;
            font-size: 18px;
            margin-bottom: 10px;
        }

        .stat-content {
            font-size: 16px;
            color: #444;
            line-height: 1.6;
        }

        .stat-content p {
            margin: 5px 0;
            font-weight: bold;
        }

        /* Conteneur des graphiques (alignés horizontalement) */
        .graph-container {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .chart-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
            width: 30%;
            min-width: 280px;
            text-align: center;
        }

        .chart-card h3 {
            color: #333;
            font-size: 20px;
            margin-bottom: 15px;
        }

        canvas {
            max-width: 100%;
        }

        /* Filtres de date alignés horizontalement */
        .date-filters {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .date-filters label {
            font-weight: bold;
            margin-right: 5px;
        }

        .date-filters input {
            padding: 5px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        /* Dropdown des box */
        .dropdown {
            position: relative;
            display: inline-block;
            margin-bottom: 10px;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: white;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
            padding: 10px;
            border-radius: 5px;
            z-index: 1000;
        }

        .dropdown-content label {
            display: block;
            margin-bottom: 5px;
            cursor: pointer;
        }

        .dropdown.active .dropdown-content {
            display: block;
        }

    </style>

</head>
<body class="stats-page">
<h1>Statistiques de vos locations</h1>

<!-- Statistiques globales -->
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


<!-- Graphiques -->
<div class="chart-card">
    <h3>Chiffre d'Affaires</h3>

    <!-- Sélecteur de période -->
    <div class="date-filters">
        <label for="startDateRevenue">Début :</label>
        <input type="month" id="startDateRevenue" value="<?= date('Y-m', strtotime('-1 month')) ?>">

        <label for="endDateRevenue">Fin :</label>
        <input type="month" id="endDateRevenue" value="<?= date('Y-m') ?>">
    </div>

    <!-- Informations sur le CA -->
    <div class="stat-content">
        <p><strong>CA Max sur la période :</strong> <span id="caMax"><?= number_format($caMaxMensuel, 2) ?></span> €</p>
        <p><strong>CA Actuel :</strong> <span id="caActuel"><?= number_format($caActuel, 2) ?></span> €</p>
        <p><strong>CA Restant :</strong> <span id="caRestant"><?= number_format($caRestant, 2) ?></span> €</p>
    </div>

    <!-- Graphique du CA -->
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
        const startDateInput = document.getElementById("startDateRevenue");
        const endDateInput = document.getElementById("endDateRevenue");
        const caMaxElem = document.getElementById("caMax");
        const caActuelElem = document.getElementById("caActuel");
        const caRestantElem = document.getElementById("caRestant");

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

        function updateCAData() {
            const startMonth = startDateInput.value;
            const endMonth = endDateInput.value;

            if (!startMonth || !endMonth || startMonth > endMonth) {
                alert("Sélectionnez une période valide !");
                return;
            }

            // Envoyer une requête AJAX pour récupérer les nouvelles données
            fetch(`routeur.php?route=get-ca-periode&debut=${startMonth}&fin=${endMonth}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        caMaxElem.innerText = data.caMax.toFixed(2) + " €";
                        caActuelElem.innerText = data.caActuel.toFixed(2) + " €";
                        caRestantElem.innerText = data.caRestant.toFixed(2) + " €";

                        // Mettre à jour le graphique CA
                        revenueChart.data.labels = data.moisLabels;
                        revenueChart.data.datasets[0].data = data.revenuMensuelData;
                        revenueChart.update();
                    } else {
                        alert("Erreur lors de la récupération des données.");
                    }
                })
                .catch(error => console.error("Erreur:", error));
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
        startDateInput.addEventListener("change", updateCAData);
        endDateInput.addEventListener("change", updateCAData);
    });

</script>
</body>
</html>