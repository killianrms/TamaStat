<!DOCTYPE html>
<html lang="fr">
<body>

<main>
    <h2>Se connecter</h2>
    <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="post" class="form-login">
        <legend>Formulaire de Connexion Utilisateur</legend>
        <input type="hidden" name="action" value="connecter">
        <input type="hidden" name="class" value="App\Controleur\Specifique\ControleurUtilisateur">
        <div class="form-group">
            <label for="email">Email :</label>
            <input type="email" id="email" name="email" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$" required
                   placeholder="jean.yves@gmail.com">
        </div>
        <div class="form-group">
            <label for="mdp">Mot de passe :</label>
            <input type="password" id="mdp" name="mdp" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%^&*]).{8,}"
                   required placeholder="Azerty34!">
        </div>
        <input type="submit" value="Se connecter" class="submit-btn">
    </form>
</main>

</body>
</html>
