<?php
USE App\Configuration\ConnexionBD;
USE App\Modele\CsvModele;

$connexion = new ConnexionBD();
$pdo = $connexion->getPdo();
$csvModele = new CsvModele();

$utilisateurId = $_SESSION['user']['id'];

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

if ($hasBoxes && $hasBoxesConfig && $hasContrats && $hasFactures) {
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
        <form action="routeur.php?route=importer-box" method="POST" enctype="multipart/form-data">
            <label for="csv_box">Importer un fichier CSV des box :</label>
            <input type="file" id="csv_box" name="csv_box" accept=".csv" required>
            <button type="submit">Importer</button>
        </form>
    </div>
<?php elseif (!$hasBoxesConfig): ?>
    <div class="step">
        <h2>Étape 2/4 : Configurer vos box</h2>
        <form action="routeur.php?route=configurer-box" method="POST">
            <?php
            $stmt = $pdo->prepare('SELECT * FROM box_types WHERE utilisateur_id = ?');
            $stmt->execute([$utilisateurId]);
            $boxTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($boxTypes as $boxType) {
                echo '<label for="box_' . $boxType['id'] . '">Nombre de box ' . $boxType['denomination'] . ' :</label>';
                echo '<input type="number" id="box_' . $boxType['id'] . '" name="box_' . $boxType['id'] . '" min="0" required><br>';
            }
            ?>
            <button type="submit">Enregistrer</button>
        </form>
    </div>
<?php elseif (!$hasContrats): ?>
    <div class="step">
        <h2>Étape 3/4 : Importer vos contrats</h2>
        <form action="routeur.php?route=importer-contrats" method="POST" enctype="multipart/form-data">
            <label for="csv_contrats">Importer un fichier CSV des contrats :</label>
            <input type="file" id="csv_contrats" name="csv_contrats" accept=".csv" required>
            <button type="submit">Importer</button>
        </form>
    </div>

<?php elseif (!$hasFactures): ?>
    <div class="step">
        <h2>Étape 4/4 : Importer vos factures</h2>
        <form action="routeur.php?route=importer-factures" method="POST" enctype="multipart/form-data">
            <label for="csv_factures">Importer un fichier CSV des factures :</label>
            <input type="file" id="csv_factures" name="csv_factures" accept=".csv" required>
            <button type="submit">Importer</button>
        </form>
    </div>
<?php endif; ?>
</body>
</html>
