<?php
use App\Configuration\ConnexionBD;
use App\Modele\CsvModele;

$connexion = new ConnexionBD();
$pdo = $connexion->getPdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $fileExt = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));
    if ($fileExt !== 'csv') {
        echo "Le fichier doit être au format CSV.";
    } else {
        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        $csvData = [];

        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            $csvData[] = $data;
        }
        fclose($handle);

        $boxDetails = [];
        foreach ($csvData as $row) {
            // Vérification de l'existence des indices avant de les utiliser
            $taille = isset($row[7]) ? $row[7] : null;
            $prix = isset($row[9]) ? $row[9] : null;
            $loues = isset($row[12]) && $row[12] !== '' ? 1 : 0;

            if ($taille && $prix !== null) {
                if (!isset($boxDetails[$taille])) {
                    $boxDetails[$taille] = ['total' => 0, 'loues' => 0, 'prix' => $prix];
                }
                $boxDetails[$taille]['total']++;
                $boxDetails[$taille]['loues'] += $loues;
            }
        }

        foreach ($boxDetails as $taille => $details) {
            $stmt = $pdo->prepare('SELECT nombre_box, prix_par_m3 FROM boxes_utilisateur WHERE utilisateur_id = :utilisateur_id AND taille = :taille');
            $stmt->bindParam(':utilisateur_id', $_SESSION['user']['id']);
            $stmt->bindParam(':taille', $taille);
            $stmt->execute();
            $userBox = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($userBox) {
                $restants = $userBox['nombre_box'] - $details['loues'];
                $revenuePotentiel = $restants * $userBox['prix_par_m3'] * $taille;

                echo "<p>Taille du box : $taille m³</p>";
                echo "<p>Box total : " . $userBox['nombre_box'] . "</p>";
                echo "<p>Box loués : " . $details['loues'] . "</p>";
                echo "<p>Box restants à louer : $restants</p>";
                echo "<p>Revenue potentiel à venir : " . number_format($revenuePotentiel, 2) . " €</p>";
            } else {
                echo "<p>Aucune donnée disponible pour la taille $taille m³ dans votre base de données.</p>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques</title>
</head>
<body>

<h1>Statistiques de vos box</h1>

<form action="routeur.php?route=stats" method="POST" enctype="multipart/form-data">
    <label for="csv_file">Importer un fichier CSV :</label>
    <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
    <br>
    <button type="submit">Importer</button>
</form>

</body>
</html>
