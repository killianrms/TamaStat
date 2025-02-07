<?php
require_once __DIR__ . '/../../Configuration/ConnexionBD.php';
require_once __DIR__ . '/../../Modele/CsvModele.php';

$connexion = new ConnexionBD();
$pdo = $connexion->getPdo();
$csvModele = new CsvModele();

$etape = $_GET['etape'] ?? 'importer-box';

if (!isset($_SESSION['user'])) {
    header('Location: routeur.php?route=connexion');
    exit;
}

$utilisateurId = $_SESSION['user']['id'];

$stmt = $pdo->prepare('SELECT COUNT(*) FROM utilisateur_boxes WHERE utilisateur_id = ?');
$stmt->execute([$utilisateurId]);
$hasBoxes = $stmt->fetchColumn() > 0;

$stmt = $pdo->prepare('SELECT COUNT(*) FROM locations WHERE utilisateur_id = ?');
$stmt->execute([$utilisateurId]);
$hasContrats = $stmt->fetchColumn() > 0;

if ($hasBoxes && $hasContrats) {
    header('Location: routeur.php?route=stats');
    exit;
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord</title>
</head>
<body class="dashboard-page">
<h1>Configuration de vos données</h1>

<div class="steps">
    <div class="step <?= $etape === 'importer-box' ? 'active' : '' ?>">
        <h2>Étape 1 : Importer vos box</h2>
        <form action="routeur.php?route=importer-box" method="POST" enctype="multipart/form-data">
            <label for="csv_box">Importer un fichier CSV des box :</label>
            <input type="file" id="csv_box" name="csv_box" accept=".csv" required>
            <button type="submit">Importer</button>
        </form>
    </div>

    <div class="step <?= $etape === 'configurer-box' ? 'active' : '' ?>">
        <h2>Étape 2 : Configurer vos box</h2>
        <form action="routeur.php?route=configurer-box" method="POST">
            <?php
            $stmt = $pdo->prepare('SELECT * FROM box_types');
            $stmt->execute();
            $boxTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($boxTypes as $boxType) {
                echo '<label for="box_' . $boxType['id'] . '">Nombre de box ' . $boxType['denomination'] . ' (' . $boxType['taille_m3'] . 'm³) :</label>';
                echo '<input type="number" id="box_' . $boxType['id'] . '" name="box_' . $boxType['id'] . '" min="0" required><br>';
            }
            ?>
            <button type="submit">Enregistrer</button>
        </form>
    </div>

    <div class="step <?= $etape === 'importer-contrats' ? 'active' : '' ?>">
        <h2>Étape 3 : Importer vos contrats</h2>
        <form action="routeur.php?route=importer-contrats" method="POST" enctype="multipart/form-data">
            <label for="csv_contrats">Importer un fichier CSV des contrats :</label>
            <input type="file" id="csv_contrats" name="csv_contrats" accept=".csv" required>
            <button type="submit">Importer</button>
        </form>
    </div>
</div>

<?php if ($hasBoxes && $hasContrats): ?>
    <a href="routeur.php?route=stats" class="button">Voir les statistiques</a>
<?php endif; ?>
</body>
</html>