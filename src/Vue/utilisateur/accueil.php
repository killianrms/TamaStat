<?php
if (!isset($_SESSION['user'])) {
    header('Location: routeur.php?route=connexion');
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
<body>
<form action="routeur.php?route=ajouterDonnees" method="POST" enctype="multipart/form-data">
    <label for="csv_file">Importer un fichier CSV :</label>
    <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
    <br>
    <button type="submit">Importer</button>
</form>
</body>
</html>
