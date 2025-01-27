<!DOCTYPE html>
<html lang="en">
<body>
<?php
/** @var App\Modele\DataObject\Utilisateur $utilisateur */
use App\Modele\Repository\Specifique\UtilisateurRepository;
$email = $utilisateur->getEmail();
$prenom = $utilisateur->getPrenom();
$nom = $utilisateur->getNom();
$adresse = $utilisateur->getAdresse();
$ville = $utilisateur->getVille();
?>
<div class="form-container">
<form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" enctype="multipart/form-data">
    <fieldset>
        <legend>Formulaire de mise à jour Utilisateur</legend>
        <input type="hidden" name="action" value="miseAJourDepuisFormulaire">
        <input type="hidden" name="class" value="App\Controleur\Specifique\ControleurUtilisateur">
        <div class="form-group">

        <label for="email">Email :</label>
            <input type="email" id="email" name="email" value="<?= $email ?>" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$">
        </div>
        <div class="form-group">

        <label for="prenom">Prénom :</label>
            <input type="text" id="prenom" name="prenom" value="<?= $prenom ?>">
        </div>
        <div class="form-group">

        <label for="nom">Nom :</label>
            <input type="text" id="nom" name="nom" value="<?= $nom ?>">
        </div>
        <div class="form-group">

        <label for="adresse">Adresse de livraison :</label>
            <input type="text" id="adresse" name="adresse" value="<?= $adresse ?>">
        </div>
        <div class="form-group">

        <label for="ville">Ville :</label>
            <input type="text" id="ville" name="ville" value="<?= $ville ?>">
        </div>
        <div class="form-group">

        <label for="mdp">Mot de passe&#42; :</label>
            <input type="password" id="mdp" name="mdp" placeholder="Exemple34!"
                   pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%^&*]).{8,}"
                   title="Doit contenir au moins un chiffre, une majuscule, une minuscule, un caractère spécial et au moins 8 caractères au total" required>
        </div>
        <div class="form-group">

        <label for="newMdp">Nouveau mot de passe :</label>
            <input type="password" id="newMdp" name="newMdp" placeholder="Exemple34!"
                   pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%^&*]).{8,}"
                   title="Doit contenir au moins un chiffre, une majuscule, une minuscule, un caractère spécial et au moins 8 caractères au total">
        </div>
        <div class="form-group">

        <label for="newMdpConfirm">Confirmer le nouveau mot de passe :</label>
            <input type="password" id="newMdpConfirm" name="newMdpConfirm" placeholder="Exemple34!"
                   pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%^&*]).{8,}"
                   title="Doit contenir au moins un chiffre, une majuscule, une minuscule, un caractère spécial et au moins 8 caractères au total">
        </div>
        <p>
            <input type="submit" value="Envoyer" />
        </p>
    </fieldset>
</form>
</div>
</body>
</html>