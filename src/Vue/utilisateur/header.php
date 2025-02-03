<header>
    <div class="navbar">
        <h1>TamaStats</h1>

        <?php if (isset($_SESSION['user'])): ?>
        <div class="nav-links">

            <a href="routeur.php?route=accueil">Accueil</a>
            <a href="routeur.php?route=stats">Stats</a>

            <?php if ($_SESSION['user']['is_admin'] === 1): ?>
                <a href="routeur.php?route=gestionUtilisateurs">Gestion Utilisateurs</a>
            <?php endif; ?>

            <a href="routeur.php?route=deconnexion">Déconnexion</a>
            <?php else: ?>
                <a href="routeur.php?route=connexion">Connexion</a>
            <?php endif; ?>
        </div>

        <div class="burger" onclick="toggleMenu()">
            <div></div>
            <div></div>
            <div></div>
        </div>
    </div>
</header>
