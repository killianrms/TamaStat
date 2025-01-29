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
?>

<h3>Vos boxes :</h3>
<?php if (!empty($boxesUtilisateur)): ?>
    <table>
        <tr><th>Taille (m³)</th><th>Quantité</th><th>Prix par m³ (€)</th></tr>
        <?php foreach ($boxesUtilisateur as $box): ?>
            <tr>
                <td><?= htmlspecialchars($box['taille']) ?></td>
                <td><?= htmlspecialchars($box['nombre_box']) ?></td>
                <td><?= htmlspecialchars($box['prix_par_m3']) ?> €</td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>Aucune box enregistrée.</p>
<?php endif; ?>

<form method="POST" action="routeur.php?route=ajouterDonneesAccueil">
    <label for="prix_par_m3">Prix par m³ (€) :</label>
    <input type="number" id="prix_par_m3" name="prix_par_m3" step="0.01" value="<?= htmlspecialchars($prixParM3) ?>" required>

    <table class="boxes-table">
        <thead>
        <tr>
            <th>Taille (m³)</th>
            <th>Nombre de Boxes</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $taillesDisponibles = [1.0, 1.5, 2.0, 2.5, 3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 9.0, 10.0];
        $boxes = [];
        foreach ($boxesUtilisateur as $box) {
            $boxes[$box['taille']] = $box['nombre_box'];
        }

        foreach ($taillesDisponibles as $tailleBox):
            ?>
            <tr>
                <td><?= $tailleBox ?> m³</td>
                <td>
                    <input type="number" name="box_<?= $tailleBox ?>" value="<?= $boxes[$tailleBox] ?? 0 ?>" min="0" required>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <button type="submit">Soumettre</button>
</form>
