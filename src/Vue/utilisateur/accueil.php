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
    $boxes[(string)$box['taille']] = $box['nombre_box'];
}

$taillesDisponibles = [1.0, 1.5, 2.0, 2.5, 3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 9.0, 10.0];
?>

<h1>Gestion de vos boxes</h1>

<form method="POST" action="routeur.php?route=ajouterDonneesAccueil">
    <label for="prix_par_m3">Prix par m³ (€) :</label>
    <input type="number" id="prix_par_m3" name="prix_par_m3" step="0.01" value="<?= htmlspecialchars($prixParM3) ?>" required>
    <br><br>
    <?php foreach ($taillesDisponibles as $tailleBox): ?>
        <label for="box_<?= str_replace('.', '_', $tailleBox) ?>">Nombre de box <?= $tailleBox ?>m³ :</label>
        <input type="number" name="box_<?= str_replace('.', '_', $tailleBox) ?>" id="box_<?= str_replace('.', '_', $tailleBox) ?>" value="<?= $boxes[(string)$tailleBox] ?? 0 ?>" min="0" required>
        <br>
    <?php endforeach; ?>
    <br>
    <button type="submit">Enregistrer</button>
</form>