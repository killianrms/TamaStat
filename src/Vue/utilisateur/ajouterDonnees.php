<?php
var_dump($_POST);
?>
<form method="POST" action="routeur.php?route=ajouterDonneesAccueil">
    <label for="prix_par_m3">Prix par m³ :</label>
    <input type="text" id="prix_par_m3" name="prix_par_m3" value="<?= htmlspecialchars($donneesUtilisateur['prix_par_m3'] ?? '') ?>" />

    <?php
    $taillesDisponibles = [1, 1.5, 2, 2.5, 3, 4, 5, 6, 7, 8, 9, 10];
    foreach ($taillesDisponibles as $tailleBox):
        $quantiteBox = $donneesUtilisateur[$tailleBox] ?? 0;
        ?>
        <label for="box_<?= $tailleBox ?>">Nombre de boxes (<?= $tailleBox ?> m³) :</label>
        <input type="number" id="box_<?= $tailleBox ?>" name="box_<?= $tailleBox ?>" value="<?= htmlspecialchars($quantiteBox) ?>" min="0" />

    <?php endforeach; ?>

    <button type="submit">Sauvegarder</button>
</form>
<pre>
<?php print_r($_POST); ?>
</pre>
