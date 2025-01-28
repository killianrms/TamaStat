<?php
if (!isset($_SESSION['user'])) {
    header('Location: routeur.php?route=connexion');
    exit;
}

use App\Configuration\ConnexionBD;
$connexion = new ConnexionBD();
$pdo = $connexion->getPdo();

$stmt = $pdo->prepare('SELECT * FROM user_box WHERE utilisateur_id = :utilisateur_id');
$stmt->bindParam(':utilisateur_id', $_SESSION['user']['id']);
$stmt->execute();
$userData = $stmt->fetch(PDO::FETCH_ASSOC);

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

<?php if ($userData): ?>
    <h2>Vos données actuelles :</h2>
    <p>Nombre de box : <?php echo htmlspecialchars($userData['nombre_box']); ?></p>
    <p>Taille totale des box (en m³) : <?php echo htmlspecialchars($userData['taille']); ?></p>
    <p>Prix par m³ : <?php echo htmlspecialchars($userData['prix_par_m3']); ?> €</p>
<?php else: ?>
    <p>Aucune donnée enregistrée. Veuillez entrer les informations ci-dessous.</p>
<?php endif; ?>

<h2>Mettre à jour vos informations :</h2>
<form action="routeur.php?route=ajouterDonneesAccueil" method="POST">
    <label for="nombre_de_box">Nombre de box :</label>
    <input type="number" id="nombre_de_box" name="nombre_de_box" value="<?php echo $userData ? htmlspecialchars($userData['nombre_box']) : ''; ?>" required>

    <label for="taille_total">Taille totale des box (en m³) :</label>
    <input type="number" step="0.1" id="taille_total" name="taille_total" value="<?php echo $userData ? htmlspecialchars($userData['taille']) : ''; ?>" required>

    <label for="prix_par_m3">Prix par m³ (€) :</label>
    <input type="number" step="0.1" id="prix_par_m3" name="prix_par_m3" value="<?php echo $userData ? htmlspecialchars($userData['prix_par_m3']) : ''; ?>" required>

    <button type="submit">Enregistrer les données</button>
</form>

</body>
</html>
