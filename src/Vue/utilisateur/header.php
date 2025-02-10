<header>
    <div class="navbar">
        <h1>TamaStats</h1>

        <div class="nav-links">
        <?php if (isset($_SESSION['user'])): ?>
            <a href="routeur.php?route=accueil">Tableau de bord</a>
        <a href="routeur.php?route=profil">Profil</a>

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
