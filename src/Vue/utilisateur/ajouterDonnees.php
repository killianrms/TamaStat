<?php
require_once __DIR__ . '/../src/Controleur/Specifique/ControleurUtilisateur.php';

$controleurUtilisateur = new ControleurUtilisateur();
$donneesUtilisateur = $controleurUtilisateur->getDonneesUtilisateur($_SESSION['user']['id']);
?>
<form method="POST" action="routeur.php?route=ajouterDonnees">
    <?php
    $tailles = [1, 1.5, 2, 2.5, 3, 4, 5, 6, 7, 8, 9, 10];
    foreach ($tailles as $taille) {
        $quantite = isset($donneesUtilisateur[$taille]) ? $donneesUtilisateur[$taille] : 0;
        ?>
        <label for="box_<?php echo $taille; ?>">Nombre de box <?php echo $taille; ?>m³ :</label>
        <input type="number" name="box_<?php echo $taille; ?>" id="box_<?php echo $taille; ?>" value="<?php echo $quantite; ?>" min="0">
        <br>
    <?php } ?>

    <label for="prix_par_m3">Prix par m³ :</label>
    <input type="number" step="0.01" name="prix_par_m3" id="prix_par_m3" value="<?php echo isset($donneesUtilisateur['prix_par_m3']) ? $donneesUtilisateur['prix_par_m3'] : ''; ?>">
    <br>

    <button type="submit">Enregistrer</button>
</form>

<pre>
<?php print_r($_POST); ?>
</pre>
