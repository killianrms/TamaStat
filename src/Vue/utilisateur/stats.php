<?php
use App\Configuration\ConnexionBD;
use App\Modele\CsvModele;
use App\Controleur\Specifique\ControleurCsv;

$connexion = new ConnexionBD();
$pdo = $connexion->getPdo();
$csvModele = new CsvModele();

// Vérifier si l'utilisateur a déjà importé un CSV
$locations = $csvModele->getLocationsByUser($_SESSION['user']['id']);
$hasCSV = !empty($locations);

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $controleurCsv = new ControleurCsv();
    try {
        $controleurCsv->importerCsv($_FILES['csv_file'], $_SESSION['user']['id']);
        header('Location: routeur.php?route=stats'); // Recharger la page
        exit;
    } catch (Exception $e) {
        echo "<div class='error-message'>Erreur : " . $e->getMessage() . "</div>";
    }
}

// Récupérer les données des boxes de l'utilisateur
$stmt = $pdo->prepare('SELECT taille, nombre_box, prix_par_m3 FROM boxes_utilisateur WHERE utilisateur_id = :utilisateur_id');
$stmt->execute(['utilisateur_id' => $_SESSION['user']['id']]);
$boxes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques</title>
    <link rel="stylesheet" href="../ressources/css/style.css">
</head>
<body>
<h1>Statistiques de vos locations</h1>

<?php if (!$hasCSV): ?>
    <!-- Afficher le formulaire d'importation si aucun CSV n'est importé -->
    <form action="routeur.php?route=stats" method="POST" enctype="multipart/form-data">
        <label for="csv_file">Importer un fichier CSV :</label>
        <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
        <button type="submit">Importer</button>
    </form>
<?php else: ?>
    <!-- Afficher les statistiques et le bouton "Modifier CSV" -->
    <div class="stats-container">
        <a href="routeur.php?route=stats&reimport=1" class="button">Modifier CSV</a>

        <!-- Calcul des statistiques -->
        <?php
        $stats = [];
        foreach ($boxes as $box) {
            $taille = $box['taille'];
            $totalBoxes = $box['nombre_box'];
            $prixParM3 = $box['prix_par_m3'];

            // Calculer les locations pour cette taille
            $locationsTaille = array_filter($locations, function($location) use ($taille) {
                return $location['nb_produits'] == $taille;
            });

            $stats[$taille] = [
                'total' => $totalBoxes,
                'loues' => count($locationsTaille),
                'revenu' => count($locationsTaille) * $taille * $prixParM3
            ];
        }
        ?>

        <!-- Affichage des statistiques -->
        <table class="stats-table">
            <thead>
            <tr>
                <th>Taille (m³)</th>
                <th>Box disponibles</th>
                <th>Box loués</th>
                <th>Taux d'occupation</th>
                <th>Revenu</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($stats as $taille => $data): ?>
                <tr>
                    <td><?= $taille ?></td>
                    <td><?= $data['total'] ?></td>
                    <td><?= $data['loues'] ?></td>
                    <td><?= round(($data['loues'] / $data['total']) * 100, 2) ?>%</td>
                    <td><?= number_format($data['revenu'], 2) ?> €</td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
</body>
</html>