<?php
USE App\Configuration\ConnexionBD;

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

if ($hasBoxes && $hasBoxesConfig && $hasContrats && $hasFactures && $hasRecapVentes) {
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
<h1>Configuration de vos données</h1>

<?php if (!$hasBoxes): ?>
    <div class="step">
        <h2>Étape 1/4 : Importer vos box</h2>
        <form id="importBoxForm" action="routeur.php?route=importer-box" method="POST" enctype="multipart/form-data">
            <label for="csv_box">Importer un fichier CSV des box :</label>
            <input type="file" id="csv_box" name="csv_box" accept=".csv" required>
            <button type="submit" id="submitBtn">Importer</button>
            <div class="loader" id="loader"></div>
        </form>
    </div>
<?php elseif (!$hasBoxesConfig): ?>
    <div class="step">
        <h2>Étape 2/4 : Configurer vos box</h2>
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
<?php elseif (!$hasContrats): ?>
    <div class="step">
        <h2>Étape 3/4 : Importer vos contrats</h2>
        <form id="importContratsForm" action="routeur.php?route=importer-contrats" method="POST" enctype="multipart/form-data">
            <label for="csv_contrats">Importer un fichier CSV des contrats :</label>
            <input type="file" id="csv_contrats" name="csv_contrats" accept=".csv" required>
            <button type="submit" id="submitBtn">Importer</button>
            <div class="loader" id="loader"></div>
        </form>
    </div>
<?php elseif (!$hasFactures): ?>
    <div class="step">
        <h2>Étape 4/4 : Importer vos factures</h2>
        <form id="importFacturesForm" action="routeur.php?route=importer-factures" method="POST" enctype="multipart/form-data">
            <label for="csv_factures">Importer un fichier CSV des factures :</label>
            <input type="file" id="csv_factures" name="csv_factures" accept=".csv" required>
            <button type="submit" id="submitBtn">Importer</button>
            <div class="loader" id="loader"></div>
        </form>
    </div>
<?php elseif (!$hasRecapVentes): ?>
    <div class="step">
        <h2>Étape 5/5 : Importer vos recap_ventes</h2>
        <form id="importRecapVentesForm" action="routeur.php?route=importer-recap-ventes" method="POST" enctype="multipart/form-data">
            <label for="csv_recap_ventes">Importer un fichier CSV des recap_ventes :</label>
            <input type="file" id="csv_recap_ventes" name="csv_recap_ventes" accept=".csv" required>
            <button type="submit" id="submitBtn">Importer</button>
            <div class="loader" id="loader"></div>
        </form>
    </div>
<?php endif; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
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
