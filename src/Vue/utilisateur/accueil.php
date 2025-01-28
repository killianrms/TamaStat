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

<form action="routeur.php?route=ajouterDonneesAccueil" method="POST">
    <h3>Ajouter ou modifier des boxes :</h3>

    <label for="prix_par_m3">Prix par m³ (€) :</label>
    <input type="number" step="0.01" id="prix_par_m3" name="prix_par_m3" required>
    <br>

    <?php
    $taillesDisponibles = [1.0, 1.5, 2.0, 2.5, 3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 9.0, 10.0];
    foreach ($taillesDisponibles as $tailleBox) {
        $boxExistante = null;
        foreach ($boxesUtilisateur as $box) {
            if ($box['taille'] == $tailleBox) {
                $boxExistante = $box;
                break;
            }
        }
        ?>

        <label for="box_<?php echo $tailleBox; ?>">Box de <?php echo $tailleBox; ?> m³ :</label>
        <input type="number" id="box_<?php echo $tailleBox; ?>" name="box_<?php echo $tailleBox; ?>" value="<?php echo $boxExistante ? htmlspecialchars($boxExistante['nombre_box']) : 0; ?>" required>
        <br>

        <?php
    }
    ?>

    <button type="submit">Mettre à jour les boxes</button>
</form>

