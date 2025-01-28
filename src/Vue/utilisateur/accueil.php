<?php
if (!isset($_SESSION['user'])) {
    header('Location: routeur.php?route=connexion');
    exit;
}

use App\Controleur\Specifique\ControleurUtilisateur;

$controleurUtilisateur = new ControleurUtilisateur();

$userData = $controleurUtilisateur->recupererDonneesUtilisateur($_SESSION['user']['id']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil</title>
</head>
<body>

<h1>Bienvenue sur votre page d'accueil</h1>

<form action="routeur.php?route=ajouterDonneesAccueil" method="POST">
    <label for="nombre_de_box">Nombre de box :</label>
    <input type="number" id="nombre_de_box" name="nombre_de_box"
           value="<?php echo isset($userData['nombre_box']) ? htmlspecialchars($userData['nombre_box']) : ''; ?>" required>

    <br>

    <label for="taille_total">Taille totale des box (en m³) :</label>
    <input type="number" step="0.1" id="taille_total" name="taille_total"
           value="<?php echo isset($userData['taille']) ? htmlspecialchars($userData['taille']) : ''; ?>" required>

    <br>

    <label for="prix_par_m3">Prix par m³ (€) :</label>
    <input type="number" step="0.1" id="prix_par_m3" name="prix_par_m3"
           value="<?php echo isset($userData['prix_par_m3']) ? htmlspecialchars($userData['prix_par_m3']) : ''; ?>" required>

    <br>

    <button type="submit">Enregistrer les données</button>
</form>

</body>
</html>
