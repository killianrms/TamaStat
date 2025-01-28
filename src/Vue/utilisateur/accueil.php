<?php
if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
    header('Location: routeur.php?route=connexion');
    exit;
}

$userId = $_SESSION['user']['id'] ?? null;
$username = $_SESSION['user']['username'] ?? 'Utilisateur';
$role = $_SESSION['user']['role'] ?? 'Visiteur';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page d'Accueil</title>
</head>
<body>

<h1>Bienvenue, <?php echo htmlspecialchars($username); ?> !</h1>

<form action="routeur.php?route=ajouterDonneesAccueil" method="POST">
    <label for="nombre_de_box">Nombre de Box :</label>
    <input type="number" id="nombre_de_box" name="nombre_de_box" required>
    <br>

    <label for="taille_total">Taille Totale (en m³) :</label>
    <input type="number" id="taille_total" name="taille_total" required>
    <br>

    <label for="prix_par_m3">Prix par m³ (€) :</label>
    <input type="number" step="0.01" id="prix_par_m3" name="prix_par_m3" required>
    <br>

    <button type="submit">Soumettre</button>
</form>

</body>
</html>
