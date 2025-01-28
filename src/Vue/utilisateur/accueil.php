<?php
if (!isset($_SESSION['user'])) {
    header('Location: routeur.php?route=connexion');
    exit;
}

use App\Controleur\Specifique\ControleurUtilisateur;

$controleurUtilisateur = new ControleurUtilisateur();
$donneesUtilisateur = $controleurUtilisateur->getDonneesUtilisateur($_SESSION['user']['id']);  // Récupère les données de l'utilisateur

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil</title>
</head>
<body>

<h1>Bienvenue sur la page d'accueil</h1>

<?php if ($donneesUtilisateur): ?>
    <p>Nombre de box : <?php echo htmlspecialchars($donneesUtilisateur['nombre_de_box']); ?></p>
    <p>Taille totale : <?php echo htmlspecialchars($donneesUtilisateur['taille_total']); ?> m³</p>
    <p>Prix par m³ : <?php echo htmlspecialchars($donneesUtilisateur['prix_par_m3']); ?> €</p>
    <form action="routeur.php?route=ajouterDonneesAccueil" method="POST">
        <h3>Modifier les données :</h3>
        <label for="nombre_de_box">Nombre de Box :</label>
        <input type="number" id="nombre_de_box" name="nombre_de_box" value="<?php echo htmlspecialchars($donneesUtilisateur['nombre_de_box']); ?>" required>
        <br>
        <label for="taille_total">Taille Totale (en m³) :</label>
        <input type="number" id="taille_total" name="taille_total" value="<?php echo htmlspecialchars($donneesUtilisateur['taille_total']); ?>" required>
        <br>
        <label for="prix_par_m3">Prix par m³ (€) :</label>
        <input type="number" step="0.01" id="prix_par_m3" name="prix_par_m3" value="<?php echo htmlspecialchars($donneesUtilisateur['prix_par_m3']); ?>" required>
        <br>
        <button type="submit">Modifier</button>
    </form>
<?php else: ?>
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
<?php endif; ?>

</body>
</html>
