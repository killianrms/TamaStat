<?php
use App\Configuration\ConnexionBD;

$connexion = new ConnexionBD();
$pdo = $connexion->getPdo();

$stmt = $pdo->prepare('
    SELECT taille, nombre_box, prix_par_m3 FROM boxes_utilisateur
    WHERE utilisateur_id = :utilisateur_id
');
$stmt->bindParam(':utilisateur_id', $_SESSION['user']['id']);
$stmt->execute();
$boxesUtilisateur = $stmt->fetchAll(PDO::FETCH_ASSOC);

$prixParM3 = $boxesUtilisateur[0]['prix_par_m3'] ?? null;

$boxes = [];
foreach ($boxesUtilisateur as $box) {
    $boxes[$box['taille']] = $box['nombre_box'];
}

$taillesDisponibles = [1.0, 1.5, 2.0, 2.5, 3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 9.0, 10.0];
?>

<h3>Gestion de vos boxes</h3>

<form method="POST" action="routeur.php?route=ajouterDonnees">
    <label for="prix_par_m3">Prix par m³ (€) :</label>
    <input type="number" id="prix_par_m3" name="prix_par_m3" step="0.01" value="<?= htmlspecialchars($prixParM3) ?>" required>
    <br><br>

    <?php foreach ($taillesDisponibles as $tailleBox): ?>
        <label for="box_<?= $tailleBox ?>">Nombre de box <?= $tailleBox ?>m³ :</label>
        <input type="number" name="box_<?= $tailleBox ?>" id="box_<?= $tailleBox ?>" value="<?= $boxes[$tailleBox] ?? 0 ?>" min="0" required>
        <br>
    <?php endforeach; ?>

    <br>
    <button type="submit">Enregistrer</button>
</form>
