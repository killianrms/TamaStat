<!DOCTYPE html>
<html lang="fr">
<body>
<div class="form-container">
    <?php
    /** @var Produit $produit */
    use App\Modele\DataObject\Produit;
    $id = htmlspecialchars($produit->getId());
    $nom = htmlspecialchars($produit->getNom());
    $prix = htmlspecialchars($produit->getPrix());
    $nomPhoto = htmlspecialchars($produit->getNomPhoto());
    ?>
    <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" enctype="multipart/form-data">
        <fieldset>
            <legend>Formulaire de mise à jour : <?= $nom ?></legend>
            <input type="hidden" name="action" value="miseAJourDepuisFormulaire">
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="form-group">
            <label for="Nom_id">Nom :</label>
                <input type="text" name="nom" id="Nom_id" value="<?= $nom ?>" required>
            </div>
            <p>
                <label for="Prix_id">Prix :</label>
                <input type="text" name="prix" id="Prix_id" value="<?= $prix ?>" required>
            </p>
            <p>
                <label for="Image_id">Téléchargez une nouvelle image :</label>
                <input type="file" name="image" id="Image_id" accept="image/*">
            </p>
            <p>
                <input type="submit" value="Envoyer">
            </p>
        </fieldset>
    </form>
</div>
</body>
</html>
