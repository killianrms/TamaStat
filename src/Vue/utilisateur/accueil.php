<?php

use App\Configuration\ConnexionBD;

$connexion = new ConnexionBD();
$pdo = $connexion->getPdo();

$utilisateurId = $_SESSION['user']['id'];

// Vérifications de l'existence des données (boxes, config, contrats, factures)
$stmt = $pdo->prepare('SELECT COUNT(*) FROM box_types WHERE utilisateur_id = ?');
$stmt->execute([$utilisateurId]);
$hasBoxes = $stmt->fetchColumn() > 0;

$stmt = $pdo->prepare('SELECT COUNT(*) FROM utilisateur_boxes WHERE utilisateur_id = ?');
$stmt->execute([$utilisateurId]);
$hasBoxesConfig = $stmt->fetchColumn() > 0;

$stmt = $pdo->prepare('SELECT COUNT(*) FROM locations WHERE utilisateur_id = ?');
$stmt->execute([$utilisateurId]);
$hasContrats = $stmt->fetchColumn() > 0;

$stmt = $pdo->prepare('SELECT COUNT(*) FROM factures WHERE utilisateur_id = ?');
$stmt->execute([$utilisateurId]);
$hasFactures = $stmt->fetchColumn() > 0;

$stmt = $pdo->prepare('SELECT COUNT(*) FROM recap_ventes WHERE utilisateur_id = ?');
$stmt->execute([$utilisateurId]);
$hasRecapVentes = $stmt->fetchColumn() > 0;

$stmt = $pdo->prepare('SELECT COUNT(*) FROM contrats_clos WHERE utilisateur_id = ?');
$stmt->execute([$utilisateurId]);
$hasContratsClos = $stmt->fetchColumn() > 0;

if ($hasBoxes && $hasBoxesConfig && $hasContrats && $hasFactures && $hasRecapVentes && $hasContratsClos) {
    header("Location: routeur.php?route=stats");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil</title>
</head>
<body class="accueil-page">
<h1 style="text-align: center;">Configuration de vos données</h1>

<!-- Étape 1 -->
<?php if (!$hasBoxes): ?>
    <div class="step">
        <div class="help-bubble" onclick="toggleTooltip('boxHelp')">?</div>
        <div id="boxHelp" class="help-tooltip">
            <h3>Comment obtenir le fichier des types de boxe ?</h3>
            <ol>
                <li>Connectez-vous à votre compte Vialtic Mondial Box</li>
                <li>Allez dans la section "Paramètres"</li>
                <li>Sélectionnez "Types de boxes"</li>
                <li>Cliquez sur "Colonnes affichées" et vérifiez que toutes les cases soient coché</li>
                <li>Cliquez sur "Réinitialiser l'ordre des données"</li>
                <li>Téléchargez le fichier CSV généré</li>
                <li>Importez-le ici en cliquant sur "Parcourir"</li>
            </ol>
        </div>

        <h2>Étape 1/6 : Importer vos box</h2>
        <form id="importBoxForm" action="routeur.php?route=importer-box" method="POST" enctype="multipart/form-data">
            <label for="csv_box">Fichier CSV des types de boxes :</label>
            <input type="file" id="csv_box" name="csv_box" accept=".csv" required>
            <button type="submit" id="submitBtn">Importer</button>
            <div class="loader" id="loader"></div>
        </form>
    </div>

    <!-- Étape 2 -->
<?php elseif (!$hasBoxesConfig): ?>
    <div class="step">
        <div class="help-bubble" onclick="toggleTooltip('quantiteHelp')">?</div>
        <div id="quantiteHelp" class="help-tooltip">
            <h3>Pourquoi configurer la quantité de mes boxes ?</h3>
            <p>Ces informations sont essentielles pour calculer le taux d'occupation.</p>
            <p>Générer des statistiques précises</p>
            <p>Optimiser la gestion de votre espace</p>
        </div>

        <h2>Étape 2/6 : Configurer vos boxes</h2>
        <form id="configBoxForm" action="routeur.php?route=configurer-box" method="POST">
            <?php
            $stmt = $pdo->prepare('SELECT * FROM box_types WHERE utilisateur_id = ?');
            $stmt->execute([$utilisateurId]);
            $boxTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($boxTypes as $boxType) {
                echo '<label for="box_' . $boxType['id'] . '">Nombre de box ' . $boxType['denomination'] . ' :</label>';
                echo '<input type="number" id="box_' . $boxType['id'] . '" name="box_' . $boxType['id'] . '" min="0" required><br>';
            }
            ?>
            <button type="submit" id="submitBtn">Enregistrer</button>
            <div class="loader" id="loader"></div>
        </form>
    </div>

    <!-- Étape 3 -->
<?php elseif (!$hasContrats): ?>
    <div class="step">
        <div class="help-bubble" onclick="toggleTooltip('contratsHelp')">?</div>
        <div id="contratsHelp" class="help-tooltip">
            <h3>Comment importer vos contrats en cours ?</h3>
            <ol>
                <li>Connectez-vous à votre compte Vialtic Mondial Box</li>
                <li>Allez dans la section "Contrats"</li>
                <li>Cliquez sur "Colonnes affichées" et vérifiez que toutes les cases soient coché</li>
                <li>Cliquez sur "Réinitialiser l'ordre des données"</li>
                <li>Téléchargez le fichier CSV généré</li>
                <li>Importez-le ici en cliquant sur "Parcourir"</li>
            </ol>
        </div>
        <h2>Étape 3/6 : Importer vos contrats en cours</h2>
        <form id="importContratsForm" action="routeur.php?route=importer-contrats" method="POST"
              enctype="multipart/form-data">
            <label for="csv_contrats">Importer un fichier CSV des contrats :</label>
            <input type="file" id="csv_contrats" name="csv_contrats" accept=".csv" required>
            <button type="submit" id="submitBtn">Importer</button>
            <div class="loader" id="loader"></div>
        </form>
    </div>

    <!-- Étape 4 -->
<?php elseif (!$hasContratsClos): ?>
    <div class="step">
        <div class="help-bubble" onclick="toggleTooltip('contratsClosHelp')">?</div>
        <div id="contratsClosHelp" class="help-tooltip">
            <h3>Comment importer vos contrats clos ?</h3>
            <ol>
                <li>Connectez-vous à votre compte Vialtic Mondial Box</li>
                <li>Allez dans la section "Contrats"</li>
                <li>Cochez "Contrats clôturés"</li>
                <li>Cliquez sur "Colonnes affichées" et vérifiez que toutes les cases soient coché</li>
                <li>Cliquez sur "Réinitialiser l'ordre des données"</li>
                <li>Téléchargez le fichier CSV généré</li>
                <li>Importez-le ici en cliquant sur "Parcourir"</li>
            </ol>
        </div>
        <h2>Étape 4/6 : Importer vos contrats clos</h2>
        <form id="importContratsClosForm" action="routeur.php?route=importer-contrats-clos" method="POST"
              enctype="multipart/form-data">
            <label for="csv_contrats_clos">Importer un fichier CSV des contrats clos :</label>
            <input type="file" id="csv_contrats_clos" name="csv_contrats_clos" accept=".csv" required>
            <button type="submit" id="submitBtn">Importer</button>
            <div class="loader" id="loader"></div>
        </form>
    </div>

    <!-- Étape 5 -->
<?php elseif (!$hasFactures): ?>
    <div class="step">
        <div class="help-bubble" onclick="toggleTooltip('facturesHelp')">?</div>
        <div id="facturesHelp" class="help-tooltip">
            <h3>Comment importer mes factures ?</h3>
            <ol>
                <li>Connectez-vous à votre compte Vialtic Mondial Box</li>
                <li>Allez dans la section "factures"</li>
                <li>Cliquez sur "Colonnes affichées" et vérifiez que toutes les cases soient coché</li>
                <li>Cliquez sur "Réinitialiser l'ordre des données"</li>
                <li>Téléchargez le fichier CSV généré</li>
                <li>Importez-le ici en cliquant sur "Parcourir"</li>
            </ol>
        </div>
        <h2>Étape 5/6 : Importer vos factures</h2>
        <form id="importFacturesForm" action="routeur.php?route=importer-factures" method="POST"
              enctype="multipart/form-data">
            <label for="csv_factures">Importer un fichier CSV des factures :</label>
            <input type="file" id="csv_factures" name="csv_factures" accept=".csv" required>
            <button type="submit" id="submitBtn">Importer</button>
            <div class="loader" id="loader"></div>
        </form>
    </div>

    <!-- Étape 6 -->
<?php elseif (!$hasRecapVentes): ?>
    <div class="step">
        <div class="help-bubble" onclick="toggleTooltip('recapVentesHelp')">?</div>
        <div id="recapVentesHelp" class="help-tooltip">
            <h3>Configuration des box</h3>
            <ol>
                <li>Connectez-vous à votre compte Vialtic Mondial Box</li>
                <li>Allez dans la section "Gestion"</li>
                <li>Sélectionnez "Recap vente"</li>
                <li>Téléchargez le fichier CSV généré</li>
                <li>Importez-le ici en cliquant sur "Parcourir"</li>
            </ol>
        </div>
        <h2>Étape 6/6 : Importer vos recap_ventes</h2>
        <form id="importRecapVentesForm" action="routeur.php?route=importer-recap-ventes" method="POST"
              enctype="multipart/form-data">
            <label for="csv_recap_ventes">Importer un fichier CSV des recap_ventes :</label>
            <input type="file" id="csv_recap_ventes" name="csv_recap_ventes" accept=".csv" required>
            <button type="submit" id="submitBtn">Importer</button>
            <div class="loader" id="loader"></div>
        </form>
    </div>
<?php endif; ?>

<!-- Fenêtre modale pour agrandir les GIF -->
<div id="gifModal" class="gif-modal" onclick="closeGif()">
    <img id="gifLarge" src="" alt="Agrandissement GIF">
</div>

<script>
    function toggleTooltip(id) {
        const tooltip = document.getElementById(id);
        tooltip.style.display = tooltip.style.display === 'block' ? 'none' : 'block';
    }

    document.addEventListener('click', function (e) {
        if (!e.target.classList.contains('help-bubble')) {
            document.querySelectorAll('.help-tooltip').forEach(tooltip => {
                tooltip.style.display = 'none';
            });
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function (e) {
                const submitBtn = form.querySelector('#submitBtn');
                const loader = form.querySelector('#loader');

                submitBtn.disabled = true;
                loader.style.display = 'block';

                setTimeout(() => {
                    submitBtn.disabled = false;
                    loader.style.display = 'none';
                }, 300000); // 5 minutes
            });
        });
    });
</script>
</body>
</html>
