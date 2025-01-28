<?php
if (!isset($_SESSION['user'])) {
    header('Location: routeur.php?route=connexion');
    exit;
}

use App\Controleur\Specifique\ControleurUtilisateur;

$controleurUtilisateur = new ControleurUtilisateur();

$donneesUtilisateur = $controleurUtilisateur->getDonneesUtilisateur($_SESSION['user']['id']);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil</title>
</head>
<body>
<h1>Bienvenue, <?php echo htmlspecialchars($_SESSION['user']['nom_utilisateur']); ?></h1>

<?php if (!empty($donneesUtilisateur)): ?>
    <p>Nombre de box : <?php echo htmlspecialchars($donneesUtilisateur['nombre_box']); ?></p>
    <p>Taille totale : <?php echo htmlspecialchars($donneesUtilisateur['taille']); ?> m³</p>
    <p>Prix par m³ : <?php echo htmlspecialchars($donneesUtilisateur['prix_par_m3']); ?> €</p>

    <form action="routeur.php?route=ajouterDonneesAccueil" method="POST">
        <h3>Modifier les données :</h3>
        <label for="nombre_box">Nombre de Box :</label>
        <input type="number" id="nombre_box" name="nombre_box" value="<?php echo htmlspecialchars($donneesUtilisateur['nombre_box']); ?>" required>
        <br>
        <label for="taille">Taille Totale (en m³) :</label>
        <input type="number" step="0.01" id="taille" name="taille" value="<?php echo htmlspecialchars($donneesUtilisateur['taille']); ?>" required>
        <br>
        <label for="prix_par_m3">Prix par m³ (€) :</label>
        <input type="number" step="0.01" id="prix_par_m3" name="prix_par_m3" value="<?php echo htmlspecialchars($donneesUtilisateur['prix_par_m3']); ?>" required>
        <br>
        <button type="submit">Modifier</button>
    </form>
<?php else: ?>
    <form action="routeur.php?route=ajouterDonneesAccueil" method="POST">
        <h3>Ajouter des données :</h3>
        <label for="nombre_box">Nombre de Box :</label>
        <input type="number" id="nombre_box" name="nombre_box" required>
        <br>
        <label for="taille">Taille Totale (en m³) :</label>
        <input type="number" step="0.01" id="taille" name="taille" required>
        <br>
        <label for="prix_par_m3">Prix par m³ (€) :</label>
        <input type="number" step="0.01" id="prix_par_m3" name="prix_par_m3" required>
        <br>
        <button type="submit">Ajouter</button>
    </form>
<?php endif; ?>
</body>
</html>
