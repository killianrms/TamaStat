<?php
use App\Configuration\ConnexionBD;

$connexion = new ConnexionBD();
$pdo = $connexion->getPdo();

$stmt = $pdo->prepare('
    SELECT * FROM boxes_utilisateur
    WHERE utilisateur_id = :utilisateur_id
');
$stmt->bindParam(':utilisateur_id', $_SESSION['user']['id']);
$stmt->execute();

$boxesUtilisateur = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer le prix par m³ de l'utilisateur (si disponible)
$stmt = $pdo->prepare('
    SELECT prix_par_m3 FROM boxes_utilisateur
    WHERE utilisateur_id = :utilisateur_id
    LIMIT 1
');
$stmt->bindParam(':utilisateur_id', $_SESSION['user']['id']);
$stmt->execute();

$prixParM3 = $stmt->fetchColumn();
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
        foreach ($taillesDisponibles as $tailleBox):
            // Vérifier si des boxes existent pour cette taille
            $boxExistante = null;
            $sql = "SELECT * FROM boxes_utilisateur WHERE utilisateur_id = :utilisateur_id AND taille = :taille";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':utilisateur_id', $_SESSION['user']['id']);
            $stmt->bindParam(':taille', $tailleBox);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $boxExistante = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            ?>
            <tr>
                <td><?= $tailleBox ?> m³</td>
                <td>
                    <input type="number" name="box_<?= $tailleBox ?>" value="<?= $boxExistante ? htmlspecialchars($boxExistante['nombre_box']) : 0 ?>" min="0" required>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <button type="submit">Soumettre</button>
</form>
