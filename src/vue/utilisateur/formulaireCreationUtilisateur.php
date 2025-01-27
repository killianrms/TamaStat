<!DOCTYPE html>
<html lang="fr">
<body>
    <div class="form-container">
        <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="post" class="form-create-item">
            <fieldset>
                <legend>Formulaire de création Utilisateur</legend>
                <input type="hidden" name="action" value="creerDepuisFormulaire">
                <input type="hidden" name="class" value="App\Controleur\Specifique\ControleurUtilisateur">
                <div class="form-group">
                    <label for="email">Email&#42; :</label>
                    <input type="email" id="email" name="email" placeholder="jean.yves@gmail.com" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$" required>
                </div>
                <div class="form-group">
                <label for="prenom">Prénom&#42; :</label>
                    <input type="text" id="prenom" name="prenom" placeholder="Jean" required>
                </div>
                <div class="form-group">

                <label for="nom">Nom&#42; :</label>
                    <input type="text" id="nom" name="nom" placeholder="Yves" required>
                </div>
                <div class="form-group">

                <label for="adresse">Adresse de livraison&#42; :</label>
                    <input type="text" id="adresse" name="adresse" placeholder="570 route de Ganges" required>
                </div>
                <div class="form-group">

                <label for="ville">Ville&#42; :</label>
                    <input type="text" id="ville" name="ville" placeholder="Montpellier" required>
                </div>
                <div class="form-group">

                <label for="mdp">Mot de passe&#42; :</label>
                    <input type="password" id="mdp" name="mdp" placeholder="Exemple34!"
                           pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%^&*]).{8,}"
                           title="Doit contenir au moins un chiffre, une majuscule, une minuscule, un caractère spécial et au moins 8 caractères au total" required>
                </div>
                <div class="form-group">

                <label for="mdpConfirm">Confirmer le mot de passe&#42; :</label>
                    <input type="password" id="mdpConfirm" name="mdpConfirm" placeholder="Exemple34!"
                            pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%^&*]).{8,}"
                            title="Doit contenir au moins un chiffre, une majuscule, une minuscule, un caractère spécial et au moins 8 caractères au total" required>
                </div>
                <p>
                    <input type="submit" value="Envoyer" />
                </p>
            </fieldset>
        </form>
    </div>
</body>
</html>
