<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../ressources/css/style.css">
    <script src="../ressources/js/script.js" defer></script>
    <title>Les rêves ambrés</title>
</head>
<body>
<header>
    <nav>
        <div class="burger-menu" onclick="toggleMenu()">
            <div></div>
            <div></div>
            <div></div>
        </div>
        <ul class="nav-links">
            <li>
                <a href="controleurFrontal.php?action=afficherAccueil">
                    <img src="../ressources/images/LRA.png" alt="logo">
                </a>
            </li>
            <li><a href="controleurFrontal.php?action=afficherAccueil">Accueil</a></li>
            <li><a href="controleurFrontal.php?action=afficherPanier&class=App\Controleur\Specifique\ControleurUtilisateur"">Panier</a></li>
            <?php
            use App\Lib\ConnexionUtilisateur;

            if (ConnexionUtilisateur::estConnecte()) {
                ?>
                <li class="dropdown">
                    <a href="controleurFrontal.php?action=afficherProfil&class=App\Controleur\Specifique\ControleurUtilisateur">Votre profil</a>
                    <ul class="dropdown-menu">
                        <li><a href="controleurFrontal.php?action=afficherProfil&class=App\Controleur\Specifique\ControleurUtilisateur">Profil</a></li>
                        <li><a href="controleurFrontal.php?action=afficherHistorique&class=App\Controleur\Specifique\ControleurUtilisateur">Historique</a></li>
                        <li><a href="controleurFrontal.php?action=deconnecter&class=App\Controleur\Specifique\ControleurUtilisateur">Déconnexion</a></li>
                    </ul>
                </li>
                <?php
            } else {
                ?>
                <li><a href="controleurFrontal.php?action=afficherCreerCompte&class=App\Controleur\Specifique\ControleurUtilisateur">Créer un compte</a></li>
                <li><a href="controleurFrontal.php?action=afficherSeConnecter&class=App\Controleur\Specifique\ControleurUtilisateur">Se connecter</a></li>
                <?php
            }
            ?>
        </ul>
        <ul class="nav-links mobile">
            <li><a href="controleurFrontal.php?action=afficherAccueil">Accueil</a></li>
            <li><a href="controleurFrontal.php?action=afficherPanier">Panier</a></li>
            <?php
            if (ConnexionUtilisateur::estConnecte()) {
                ?>
                <li class="dropdown">
                    <a href="controleurFrontal.php?action=afficherProfil&class=App\Controleur\Specifique\ControleurUtilisateur">Votre profil</a>
                    <ul class="dropdown-menu">
                        <li><a href="controleurFrontal.php?action=afficherProfil&class=App\Controleur\Specifique\ControleurUtilisateur">Profil</a></li>
                        <li><a href="controleurFrontal.php?action=afficherHistorique&class=App\Controleur\Specifique\ControleurUtilisateur">Historique</a></li>
                        <li><a href="controleurFrontal.php?action=deconnecter&class=App\Controleur\Specifique\ControleurUtilisateur">Se déconnecter</a></li>
                    </ul>
                </li>
                <?php
            } else {
                ?>
                <li><a href="controleurFrontal.php?action=afficherCreerCompte&class=App\Controleur\Specifique\ControleurUtilisateur">Créer un compte</a></li>
                <li><a href="controleurFrontal.php?action=afficherSeConnecter&class=App\Controleur\Specifique\ControleurUtilisateur">Se connecter</a></li>
                <?php
            }
            ?>
        </ul>
    </nav>
</header>
<main>
    <?php
    /**
     * @var string $cheminCorpsVue
     */
    require __DIR__ . "/$cheminCorpsVue";
    ?>
</main>
<footer>
    <div class="footer-container">
        <div class="footer-section">
            <h4>Contactez-nous</h4>
            <p>Adresse : 123 Rue des Rêves, 34000 Montpellier</p>
            <p>Téléphone : +33 4 12 34 56 78</p>
        </div>
        <div class="footer-section social-media">
            <h4>Suivez-nous</h4>
            <a href="#"><img src="../ressources/images/facebook.png" alt="Facebook"></a>
            <a href="#"><img src="../ressources/images/twitter.webp" alt="Twitter"></a>
            <a href="#"><img src="../ressources/images/instagram.jpg" alt="Instagram"></a>
        </div>
        <div class="footer-section">
            <p>&copy; 2024 Les rêves ambrés. Tous droits réservés.</p>
        </div>
    </div>
</footer>
</body>
</html>
