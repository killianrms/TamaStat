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
        // Vérifier si la box existe déjà dans la table utilisateur_boxes
        $stmt = $pdo->prepare('SELECT id FROM utilisateur_boxes WHERE box_type_id = ? AND utilisateur_id = ?');
        $stmt->execute([$boxId, $utilisateurId]);
        $existingBox = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingBox) {
            // Si la box existe, mettre à jour la quantité
            $stmt = $pdo->prepare('UPDATE utilisateur_boxes SET quantite = ? WHERE box_type_id = ? AND utilisateur_id = ?');
            $stmt->execute([$quantite, $boxId, $utilisateurId]);
        } else {
            // Si la box n'existe pas, l'insérer
            $stmt = $pdo->prepare('INSERT INTO utilisateur_boxes (utilisateur_id, box_type_id, quantite) VALUES (:utilisateur_id, :box_type_id, :quantite)');
            $stmt->execute([
                ':utilisateur_id' => $utilisateurId,
                ':box_type_id' => $boxId,
                ':quantite' => $quantite
            ]);
        }
    }

    // Mettre à jour la table import_tracking pour enregistrer le dernier import des utilisateur_boxes
    $stmt = $pdo->prepare('INSERT INTO import_tracking (utilisateur_id, table_name, date_dernier_import) 
                           VALUES (?, ?, NOW()) 
                           ON DUPLICATE KEY UPDATE date_dernier_import = NOW()');
    $stmt->execute([$utilisateurId, 'utilisateur_boxes']);

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
