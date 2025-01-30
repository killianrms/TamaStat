<?php
// Code PHP ici...
?>
<header>
    <div class="navbar">
        <h1>TamaStats</h1>

        <?php if (isset($_SESSION['user'])): ?>
            <div class="nav-links">
                <a href="routeur.php?route=accueil">Accueil</a>
                <a href="routeur.php?route=stats">Stats</a>
                <?php if (isset($_SESSION['user']) && $_SESSION['user']['is_admin'] === 1): ?>
                    <a href="routeur.php?route=gestionUtilisateurs">Gestion Utilisateurs</a>
                <?php endif; ?>
                <a href="routeur.php?route=deconnexion">Déconnexion</a>
            </div>
            <div class="burger" onclick="toggleMenu()">
                <div></div>
                <div></div>
                <div></div>
            </div>
        <?php else: ?>
        <?php endif; ?>
    </div>
</header>
