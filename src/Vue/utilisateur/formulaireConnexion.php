<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
</head>
<body class="connexion-page">
<h1>Connexion</h1>

<?php if (isset($_SESSION['erreur_connexion'])): ?>
    <div class="error-message"><?= htmlspecialchars($_SESSION['erreur_connexion']) ?></div>
    <?php unset($_SESSION['erreur_connexion']); ?>
<?php endif; ?>

<form action="routeur.php?route=login" method="POST">
    <label for="username">Nom d'utilisateur :</label>
    <input type="text" id="username" name="username" required>
    <br>
    <label for="password">Mot de passe :</label>
    <input type="password" id="password" name="password" required>
    <br>
    <button type="submit">Se connecter</button>
</form>
</body>
</html>