<?php
use App\Configuration\ConnexionBD;
use App\Controleur\Specifique\ControleurCsv;

$connexion = new ConnexionBD();
$pdo = $connexion->getPdo();
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

$succes = $erreur = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controleurCsv = new ControleurCsv();

    // Importer les box (Étape 1)
    if (isset($_FILES['csv_box']) && $_FILES['csv_box']['size'] > 0) {
        if (isset($_POST['confirm_reimport'])) {
            try {
                $controleurCsv->importerBoxTypes($_FILES['csv_box']);
                $succes = "Fichier CSV des box importé avec succès.";
            } catch (Exception $e) {
                $erreur = "Erreur : " . $e->getMessage();
            }
        } else {
            header('Location: routeur.php?route=profil&confirm_box=1');
            exit;
        }
    }

    // Importer les contrats (Étape 3)
    if (isset($_FILES['csv_contrats']) && $_FILES['csv_contrats']['size'] > 0) {
        if (isset($_POST['confirm_reimport'])) {
            try {
                $controleurCsv->importerContrats($_FILES['csv_contrats'], $utilisateurId);
                $succes = "Fichier CSV des contrats importé avec succès.";
            } catch (Exception $e) {
                $erreur = "Erreur : " . $e->getMessage();
            }
        } else {
            header('Location: routeur.php?route=profil&confirm_contrats=1');
            exit;
        }
    }

    // Importer les factures (Étape 4)
    if (isset($_FILES['csv_factures']) && $_FILES['csv_factures']['size'] > 0) {
        try {
            $controleurCsv->importerFactures($_FILES['csv_factures'], $utilisateurId);
            $succes = "Fichier CSV des factures importé avec succès.";
        } catch (Exception $e) {
            $erreur = "Erreur : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil</title>
    <script>
        function confirmerReimportation(message, formId) {
            if (confirm(message)) {
                document.getElementById(formId).submit();
            }
        }
    </script>
</head>
<body>
<h1>Profil</h1>

<?php if ($erreur): ?>
    <div style="color: red;"><?= htmlspecialchars($erreur) ?></div>
<?php endif; ?>

<?php if ($succes): ?>
    <div style="color: green;"><?= htmlspecialchars($succes) ?></div>
<?php endif; ?>

<!-- Étape 1 : Import des box -->
<h2>Étape 1 : Import des box</h2>
<p><?= $hasBoxes ? 'Données importées' : 'Données non importées' ?></p>
<form id="importBoxForm" action="routeur.php?route=profil" method="POST" enctype="multipart/form-data">
    <input type="file" name="csv_box" accept=".csv">
    <input type="hidden" name="confirm_reimport" value="true">
    <button type="button" onclick="confirmerReimportation('Importer de nouvelles box réinitialisera toutes les autres étapes.', 'importBoxForm')">
        <?= $hasBoxes ? 'Réimporter' : 'Importer' ?>
    </button>
</form>

<!-- Étape 2 : Configuration des box -->
<h2>Étape 2 : Configuration des box</h2>
<p><?= $hasBoxesConfig ? 'Configuration effectuée' : 'Configuration non effectuée' ?></p>

<!-- Étape 3 : Import des contrats -->
<h2>Étape 3 : Import des contrats</h2>
<p><?= $hasContrats ? 'Données importées' : 'Données non importées' ?></p>
<form id="importContratsForm" action="routeur.php?route=profil" method="POST" enctype="multipart/form-data">
    <input type="file" name="csv_contrats" accept=".csv">
    <input type="hidden" name="confirm_reimport" value="true">
    <button type="button" onclick="confirmerReimportation('Importer de nouveaux contrats peut nécessiter de réimporter les factures.', 'importContratsForm')">
        <?= $hasContrats ? 'Réimporter' : 'Importer' ?>
    </button>
</form>

<!-- Étape 4 : Import des factures -->
<h2>Étape 4 : Import des factures</h2>
<p><?= $hasFactures ? 'Données importées' : 'Données non importées' ?></p>
<form action="routeur.php?route=profil" method="POST" enctype="multipart/form-data">
    <input type="file" name="csv_factures" accept=".csv">
    <button type="submit"><?= $hasFactures ? 'Réimporter' : 'Importer' ?></button>
</form>

</body>
</html>
