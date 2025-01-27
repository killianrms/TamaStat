<!DOCTYPE html>
<html lang="fr">
<body>
<div class="form-container">
    <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" enctype="multipart/form-data">
        <fieldset>
            <legend>Formulaire de création Produit</legend>
            <input type="hidden" name="action" value="creerDepuisFormulaire">
            <input type="hidden" name="class" value="App\Controleur\Specifique\ControleurProduit">
            <div class="form-group">

                <label for="Nom_id">Nom :</label>
                <input type="text" placeholder="Nom" name="nom" id="Nom_id" required />
            </div>
            <div class="form-group">

                <label for="Prix_id">Prix :</label>
                <input type="text" placeholder="69" name="prix" id="Prix_id" required />
            </div>
            <div class="form-group">
                <label for="Image_id">Téléchargez une image :</label>
                <input type="file" name="image" id="Image_id" accept="image/*" required>
            </div>
            <p>
                <input type="submit" value="Envoyer" />
            </p>
        </fieldset>
    </form>
</div>
</body>
</html>
