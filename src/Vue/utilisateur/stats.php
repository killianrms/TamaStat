<?php
use App\Configuration\ConnexionBD;
use App\Modele\CsvModele;

session_start();

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
            $taille = $row[7];
            $prix = $row[9];

            if (!isset($boxDetails[$taille])) {
                $boxDetails[$taille] = ['total' => 0, 'loues' => 0, 'prix' => $prix];
            }
            $boxDetails[$taille]['total']++;
            if ($row[12] !== '') {
                $boxDetails[$taille]['loues']++;
            }
        }

        foreach ($boxDetails as $taille => $details) {
            $stmt = $pdo->prepare('SELECT nombre_box, prix FROM user_box WHERE utilisateur_id = :utilisateur_id AND taille = :taille');
            $stmt->bindParam(':utilisateur_id', $_SESSION['user']['id']);
            $stmt->bindParam(':taille', $taille);
            $stmt->execute();
            $userBox = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($userBox) {
                $restants = $userBox['nombre_box'] - $details['loues'];
                $revenuePotentiel = $restants * $userBox['prix'];

                echo "<p>Taille du box : $taille m³</p>";
                echo "<p>Box total : " . $userBox['nombre_box'] . "</p>";
                echo "<p>Box loués : " . $details['loues'] . "</p>";
                echo "<p>Box restants à louer : $restants</p>";
                echo "<p>Revenue potentiel à venir : $revenuePotentiel €</p>";
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

<form action="stats.php" method="POST" enctype="multipart/form-data">
    <label for="csv_file">Importer un fichier CSV :</label>
    <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
    <br>
    <button type="submit">Importer</button>
</form>

</body>
</html>
