<?php
$message = $_GET['message'] ?? 'Une erreur est survenue.';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erreur</title>
</head>
<body>
<div class="error-container">
    <h1>Erreur</h1>
    <p><?= htmlspecialchars($message) ?></p>
    <a href="routeur.php?route=accueil">Retour à l'accueil</a>
</div>
</body>
</html>