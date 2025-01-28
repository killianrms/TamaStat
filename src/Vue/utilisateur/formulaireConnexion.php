<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
</head>
<body>
<h1>Connexion</h1>

<?php
if (isset($_SESSION['erreur_connexion'])) {
    echo '<div style="color: red; font-weight: bold; margin-bottom: 10px;">' . htmlspecialchars($_SESSION['erreur_connexion']) . '</div>';
    unset($_SESSION['erreur_connexion']);
}
?>

<form action="routeur.php?route=login" method="POST">
    <label for="username">Nom d'utilisateur ou email:</label>
    <input type="text" id="username" name="username" required>
    <br>
    <label for="password">Mot de passe:</label>
    <input type="password" id="password" name="password" required>
    <br>
    <button type="submit">Se connecter</button>
</form>

</body>
</html>
