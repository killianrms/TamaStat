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

// Récupération des contrats clos
$contratsClos = $pdo->prepare('SELECT * FROM contrats_clos WHERE utilisateur_id = ?');
$contratsClos->execute([$utilisateurId]);
$contratsClos = $contratsClos->fetchAll(PDO::FETCH_ASSOC);

// Calcul du nombre moyen de jours occupés par type de box
$joursOccupesParBox = [];
$nombreContratsParBox = [];

foreach ($contratsClos as $contrat) {
    $typeBox = $contrat['type_box'];
    $dateEntree = strtotime($contrat['date_entree']);
    $sortieEffective = strtotime($contrat['sortie_effective']);

    if ($dateEntree && $sortieEffective) {
        $dureeOccupation = ($sortieEffective - $dateEntree) / (60 * 60 * 24); // Conversion en jours

        if (!isset($joursOccupesParBox[$typeBox])) {
            $joursOccupesParBox[$typeBox] = 0;
            $nombreContratsParBox[$typeBox] = 0;
        }

        $joursOccupesParBox[$typeBox] += $dureeOccupation;
        $nombreContratsParBox[$typeBox]++;
    }
}

// Calcul du nombre moyen de jours occupés par type de box
$moyenneJoursParBox = [];
foreach ($joursOccupesParBox as $typeBox => $totalJours) {
    $moyenneJoursParBox[$typeBox] = round($totalJours / $nombreContratsParBox[$typeBox], 2);
}

// Calcul du nombre de contrats clos par mois (sorties)
$contratsClosParMois = [];
foreach ($contratsClos as $contrat) {
    $moisSortie = date('Y-m', strtotime($contrat['sortie_effective']));
    $contratsClosParMois[$moisSortie] = ($contratsClosParMois[$moisSortie] ?? 0) + 1;
}

// Calcul du nombre de nouveaux contrats par mois (entrées)
$nouveauxContratsParMois = [];
foreach ($locations as $location) {
    $mois = date('Y-m', strtotime($location['date_debut']));
    $nouveauxContratsParMois[$mois] = ($nouveauxContratsParMois[$mois] ?? 0) + 1;
}

// Ajustement du graphique des entrées (Nombre d'entrées - Nombre de sorties)
$netContratsParMois = [];
foreach ($nouveauxContratsParMois as $mois => $entrees) {
    $sorties = $contratsClosParMois[$mois] ?? 0;
    $netContratsParMois[$mois] = $entrees - $sorties;
}

$boxLabelsJours = [];
$moyenneJoursData = [];

foreach ($moyenneJoursParBox as $typeBox => $moyenne) {
    $boxLabelsJours[] = $typeBox;
    $moyenneJoursData[] = $moyenne;
}


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
    SELECT bt.prix_ttc / 1.20 as prix_ht, ub.quantite  // Conversion directe dans la requête
    FROM utilisateur_boxes ub 
    INNER JOIN box_types bt ON ub.box_type_id = bt.id 
    WHERE ub.utilisateur_id = ? AND bt.utilisateur_id = ?
');
$stmt->execute([$utilisateurId, $utilisateurId]);
$boxData = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($boxData as $box) {
    $caMaxMensuel += $box['prix_ht'] * $box['quantite'];  // Utilisation du prix HT
}

$moisActuel = date('Y-m');
$stmt = $pdo->prepare('
    SELECT SUM(total_ht)  // On garde total_ht tel quel
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.9/dist/plugins/monthSelect/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/fr.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.9/dist/plugins/monthSelect/index.js"></script>
</head>
<body class="stats-page">
<h1>Statistiques de vos locations</h1>

<!-- Statistiques globales -->
<div class="stats-globales">
    <div class="stat-card">
        <h3>Chiffre d'Affaires</h3>
        <div class="stat-content">
            <p><strong>CA Max Mensuel (HT) :</strong> <?= number_format($caMaxMensuel, 2) ?> €</p>
            <p><strong>CA Actuel (HT) :</strong> <?= number_format($caActuel, 2) ?> €</p>
            <p><strong>CA Restant (HT) :</strong> <?= number_format($caRestant, 2) ?> €</p>
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
            <input type="text" class="month-picker" id="startDateRevenue" placeholder="Sélectionnez un mois">
            <label for="endDateRevenue">Mois fin :</label>
            <input type="text" class="month-picker" id="endDateRevenue" placeholder="Sélectionnez un mois">
            <button class="reset-dates">Réinitialiser</button>
        </div>
        <canvas id="revenuMensuelChart"></canvas>
    </div>

    <div class="chart-card">
        <h3>Nombre d'entrées - sorties</h3>
        <div class="date-filters">
            <label for="startDateEntrées">Mois début :</label>
            <input type="text" class="month-picker" id="startDateEntrées" placeholder="Sélectionnez un mois">
            <label for="endDateEntrées">Mois fin :</label>
            <input type="text" class="month-picker" id="endDateEntrées" placeholder="Sélectionnez un mois">
            <button class="reset-dates">Réinitialiser</button>
        </div>
        <canvas id="nouveauxContratsChart"></canvas>
    </div>

    <div class="chart-card">
        <h3>Quantité de Box</h3>
        <div class="dropdown">
            <button id="toggleFilter">🔽 Sélectionner les box</button>
            <div id="boxFilter" class="dropdown-content">
                <div class="filter-actions">
                    <button class="select-all">Tout sélectionner</button>
                    <button class="deselect-all">Tout supprimer</button>
                </div>
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

    <div class="chart-card">
        <h3>Nombre moyen de jours occupés par type de box</h3>
        <div class="dropdown">
            <button id="toggleFilterJours">🔽 Sélectionner les box</button>
            <div id="boxFilterJours" class="dropdown-content">
                <div class="filter-actions">
                    <button class="select-all-jours">Tout sélectionner</button>
                    <button class="deselect-all-jours">Tout supprimer</button>
                </div>
                <?php foreach ($boxLabelsJours as $index => $boxLabel): ?>
                    <label>
                        <input type="checkbox" class="box-checkbox-jours" value="<?= $index ?>" checked>
                        <?= htmlspecialchars($boxLabel) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <canvas id="moyenneJoursBoxChart"></canvas>
    </div>

</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // 1. Initialisation des datepickers avec Flatpickr
        // Initialisation des datepickers avec options compactes
        flatpickr(".month-picker", {
            locale: "fr",
            plugins: [
                new monthSelectPlugin({
                    shorthand: true,
                    dateFormat: "Y-m",
                    altFormat: "F Y",
                    theme: "light",
                    // Options supplémentaires pour compacter l'affichage
                    monthSelectorType: "static",
                    yearPosition: "above"
                })
            ],
            onReady: function(selectedDates, dateStr, instance) {
                // Ajustements spécifiques Firefox
                if (navigator.userAgent.includes("Firefox")) {
                    instance.calendarContainer.style.zIndex = "9999";
                    instance.calendarContainer.style.width = "auto";
                }

                // Ajustement de la largeur
                const monthSelectContainer = instance.calendarContainer.querySelector('.flatpickr-monthSelect-months');
                if (monthSelectContainer) {
                    monthSelectContainer.style.gridTemplateColumns = "repeat(3, 1fr)";
                    monthSelectContainer.style.gap = "5px";
                }
            }
        });

        // 2. Données des graphiques
        const boxLabelsJours = <?= json_encode($boxLabelsJours) ?>;
        const moyenneJoursData = <?= json_encode($moyenneJoursData) ?>;
        const moisLabels = <?= json_encode(array_keys($revenuMensuel)) ?>.reverse();
        const revenuMensuelData = <?= json_encode(array_values($revenuMensuel)) ?>.reverse();
        const moisContratsLabels = <?= json_encode(array_keys($nouveauxContratsParMois)) ?>.reverse();
        const nouveauxContratsData = <?= json_encode(array_values($nouveauxContratsParMois)) ?>.reverse();
        const boxLibresData = <?= json_encode(array_values($boxLibres)) ?>;
        const boxMaxData = <?= json_encode(array_values($boxMax)) ?>;
        const boxOccupeesData = <?= json_encode(array_values($boxOccupees)) ?>;
        const boxLabels = <?= json_encode($boxLabels) ?>;
        const contratsClosData = <?= json_encode(array_values($contratsClosParMois)) ?>.reverse();

        // 3. Création des graphiques
        // Graphique Moyenne des jours
        const joursCtx = document.getElementById("moyenneJoursBoxChart").getContext("2d");
        const joursChart = new Chart(joursCtx, {
            type: "bar",
            data: {
                labels: boxLabelsJours,
                datasets: [{
                    label: "Moyenne des jours occupés",
                    data: moyenneJoursData,
                    backgroundColor: "#007bff"
                }]
            }
        });

        // Graphique CA
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

        // Graphique Contrats
        const contratsCtx = document.getElementById('nouveauxContratsChart').getContext('2d');
        const contratsChart = new Chart(contratsCtx, {
            type: 'bar',
            data: {
                labels: moisContratsLabels,
                datasets: [
                    {
                        label: 'Nouveaux contrats (entrées)',
                        data: nouveauxContratsData,
                        backgroundColor: '#007bff'
                    },
                    {
                        label: 'Contrats clos (sorties)',
                        data: contratsClosData,
                        backgroundColor: '#dc3545'
                    }
                ]
            }
        });

        // Graphique Box
        const boxCtx = document.getElementById("boxLibreOccupeMaxChart").getContext("2d");
        const boxChart = new Chart(boxCtx, {
            type: "bar",
            data: {
                labels: boxLabels,
                datasets: [
                    { label: "Libres", data: boxLibresData, backgroundColor: "#28a745" },
                    { label: "Occupées", data: boxOccupeesData, backgroundColor: "#dc3545" },
                    { label: "Maximales", data: boxMaxData, backgroundColor: "#007bff" }
                ]
            }
        });

        // 4. Fonctions utilitaires
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
                filteredLabels = labels;
                filteredData = data;
            }

            chart.data.labels = filteredLabels;
            chart.data.datasets[0].data = filteredData;
            chart.update();
        }

        // 5. Gestion des événements
        // Filtres dates
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

        // Filtres box
        const toggleButton = document.getElementById("toggleFilter");
        const dropdownContent = document.getElementById("boxFilter");
        const toggleButtonJours = document.getElementById("toggleFilterJours");
        const dropdownContentJours = document.getElementById("boxFilterJours");

        toggleButton.addEventListener("click", function(event) {
            event.stopPropagation();
            dropdownContent.classList.toggle("active");
        });

        toggleButtonJours.addEventListener("click", function(event) {
            event.stopPropagation();
            dropdownContentJours.classList.toggle("active");
        });

        document.querySelectorAll(".box-checkbox").forEach((checkbox, index) => {
            checkbox.addEventListener("change", function() {
                const selectedIndexes = Array.from(document.querySelectorAll(".box-checkbox:checked"))
                    .map(cb => parseInt(cb.value));

                boxChart.data.labels = selectedIndexes.map(i => boxLabels[i]);
                boxChart.data.datasets[0].data = selectedIndexes.map(i => boxLibresData[i]);
                boxChart.data.datasets[1].data = selectedIndexes.map(i => boxOccupeesData[i]);
                boxChart.data.datasets[2].data = selectedIndexes.map(i => boxMaxData[i]);

                boxChart.update();
            });
        });

        document.querySelectorAll(".box-checkbox-jours").forEach((checkbox, index) => {
            checkbox.addEventListener("change", function() {
                const selectedIndexes = Array.from(document.querySelectorAll(".box-checkbox-jours:checked"))
                    .map(cb => parseInt(cb.value));

                joursChart.data.labels = selectedIndexes.map(i => boxLabelsJours[i]);
                joursChart.data.datasets[0].data = selectedIndexes.map(i => moyenneJoursData[i]);
                joursChart.update();
            });
        });

        document.querySelector('.select-all').addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('#boxFilter .box-checkbox').forEach(cb => {
                cb.checked = true;
                cb.dispatchEvent(new Event('change'));
            });
        });

        document.querySelector('.deselect-all').addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('#boxFilter .box-checkbox').forEach(cb => {
                cb.checked = false;
                cb.dispatchEvent(new Event('change'));
            });
        });

        document.querySelector('.select-all-jours').addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('#boxFilterJours .box-checkbox-jours').forEach(cb => {
                cb.checked = true;
                cb.dispatchEvent(new Event('change'));
            });
        });

        document.querySelector('.deselect-all-jours').addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('#boxFilterJours .box-checkbox-jours').forEach(cb => {
                cb.checked = false;
                cb.dispatchEvent(new Event('change'));
            });
        });

        // Gestion des boutons Réinitialiser dates
        document.querySelectorAll('.reset-dates').forEach(btn => {
            btn.addEventListener('click', function() {
                const card = this.closest('.chart-card');
                const dateInputs = card.querySelectorAll('.month-picker');

                dateInputs.forEach(input => {
                    input._flatpickr.clear();

                    // Déclencher la mise à jour des graphiques
                    if (input.id.includes('Revenue')) {
                        updateChartWithDates(revenueChart, moisLabels, revenuMensuelData,
                            document.getElementById('startDateRevenue'),
                            document.getElementById('endDateRevenue'));
                    } else {
                        updateChartWithDates(contratsChart, moisContratsLabels, nouveauxContratsData,
                            document.getElementById('startDateEntrées'),
                            document.getElementById('endDateEntrées'));
                    }
                });
            });
        });

        // Fermeture des menus dropdown
        document.addEventListener("click", function(event) {
            if (!toggleButton.contains(event.target) && !dropdownContent.contains(event.target)) {
                dropdownContent.classList.remove("active");
            }
            if (!toggleButtonJours.contains(event.target) && !dropdownContentJours.contains(event.target)) {
                dropdownContentJours.classList.remove("active");
            }
        });
    });
</script>
</body>
</html>