<?php
USE App\Configuration\ConnexionBD;

$connexion = new ConnexionBD();
$pdo = $connexion->getPdo();

$utilisateurId = $_SESSION['user']['id'];

// Récupérer la configuration actuelle des box
$stmt = $pdo->prepare('SELECT b.id, b.denomination, ub.quantite FROM box_types b 
                        LEFT JOIN utilisateur_boxes ub ON b.id = ub.box_type_id 
                        WHERE b.utilisateur_id = ?');
$stmt->execute([$utilisateurId]);
$boxConfig = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['box_counts'] as $boxId => $quantite) {
        $stmt = $pdo->prepare('UPDATE utilisateur_boxes SET quantite = ? WHERE box_type_id = ? AND utilisateur_id = ?');
        $stmt->execute([$quantite, $boxId, $utilisateurId]);
    }
    header('Location: routeur.php?route=profil');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier les Boxes</title>
</head>
<body class="profil-page">
<h1>Modifier la Configuration des Boxes</h1>

<form action="" method="POST">
    <?php foreach ($boxConfig as $box): ?>
        <label for="box_<?= $box['id'] ?>">Nombre de box <?= htmlspecialchars($box['denomination']) ?> :</label>
        <input type="number" id="box_<?= $box['id'] ?>" name="box_counts[<?= $box['id'] ?>]" min="0" value="<?= $box['quantite'] ?>" required>
    <?php endforeach; ?>

    <button type="submit">Enregistrer les modifications</button>
</form>
</body>
</html>
