<?php
if (!isset($_SESSION['user'])) {
    header('Location: routeur.php?route=connexion');
    exit;
}

use App\Configuration\ConnexionBD;

$connexion = new ConnexionBD();
$pdo = $connexion->getPdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombreDeBox = $_POST['nombre_de_box'];
    $tailleTotal = $_POST['taille_total'];
    $prixBox = $_POST['prix_box'];

    $stmt = $pdo->prepare('INSERT INTO utilisateur_box (utilisateur_id, taille, prix, nombre_box) 
                           VALUES (:utilisateur_id, :taille, :prix, :nombre_box)');
    $stmt->bindParam(':utilisateur_id', $_SESSION['user']['id']);
    $stmt->bindParam(':taille', $tailleTotal, PDO::PARAM_STR);
    $stmt->bindParam(':prix', $prixBox, PDO::PARAM_STR);
    $stmt->bindParam(':nombre_box', $nombreDeBox, PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo "Les données ont été enregistrées avec succès.";
    } else {
        echo "Une erreur s'est produite lors de l'enregistrement des données.";
    }
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

<h1>Bienvenue sur votre tableau de bord</h1>

<form action="accueil.php" method="POST">
    <label for="nombre_de_box">Nombre de box :</label>
    <input type="number" id="nombre_de_box" name="nombre_de_box" required>
    <br>

    <label for="taille_total">Taille totale (en m³) :</label>
    <input type="number" step="0.1" id="taille_total" name="taille_total" required>
    <br>

    <label for="prix_box">Prix par box (en €) :</label>
    <input type="number" step="0.01" id="prix_box" name="prix_box" required>
    <br>

    <button type="submit">Enregistrer</button>
</form>

<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <br>
    <a href="formulaireAjoutUtilisateur.php">
        <button type="button">Ajouter un utilisateur</button>
    </a>
<?php endif; ?>

</body>
</html>
