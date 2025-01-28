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

if (!empty($boxesUtilisateur)) {
    echo "<h3>Vos boxes :</h3>";
    echo "<table>";
    echo "<tr><th>Taille (m³)</th><th>Quantité</th><th>Prix par m³ (€)</th></tr>";

    foreach ($boxesUtilisateur as $box) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($box['taille']) . "</td>";
        echo "<td>" . htmlspecialchars($box['nombre_box']) . "</td>";
        echo "<td>" . htmlspecialchars($box['prix_par_m3']) . " €</td>";
        echo "</tr>";
    }

    echo "</table>";
}
?>

<form method="POST" action="ajouterDonnees.php.php">
    <label for="prix_par_m3">Prix par m³ (€) :</label>
    <input type="number" id="prix_par_m3" name="prix_par_m3" step="0.01" value="<?php echo isset($prixParM3) ? htmlspecialchars($prixParM3) : ''; ?>" required>

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

        $sql = "SELECT prix_par_m3 FROM boxes_utilisateur WHERE utilisateur_id = :utilisateur_id LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':utilisateur_id', $utilisateur_id);
        $stmt->execute();
        $prixParM3 = $stmt->fetchColumn();

        foreach ($taillesDisponibles as $tailleBox) {
            $boxExistante = null;
            $sql = "SELECT * FROM boxes_utilisateur WHERE utilisateur_id = :utilisateur_id AND taille = :taille";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':utilisateur_id', $utilisateur_id);
            $stmt->bindParam(':taille', $tailleBox);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $boxExistante = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            ?>
            <tr>
                <td><?php echo $tailleBox; ?> m³</td>
                <td><input type="number" name="box_<?php echo $tailleBox; ?>" value="<?php echo $boxExistante ? htmlspecialchars($boxExistante['nombre_box']) : 0; ?>" required></td>
            </tr>
        <?php } ?>
        </tbody>
    </table>

    <button type="submit">Soumettre</button>
</form>



