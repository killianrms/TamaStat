<!DOCTYPE html>
<html lang="fr">
<body>

<main class="profile-container">
    <h1>Votre profil</h1>
    <section class="profile-info">
        <?php

        use App\Lib\ConnexionUtilisateur;
        use App\Modele\Repository\Specifique\UtilisateurRepository;

        $utilisateur = UtilisateurRepository::recupererUtilisateurParEmail(ConnexionUtilisateur::getemailUtilisateurConnecte());
        ?>

        <table class="user-info-table">
            <tr>
                <th>Email :</th>
                <td><?= $utilisateur->getEmail() ?></td>
            </tr>
            <tr>
                <th>Prénom :</th>
                <td><?= $utilisateur->getPrenom() ?></td>
            </tr>
            <tr>
                <th>Nom :</th>
                <td><?= $utilisateur->getNom() ?></td>
            </tr>
            <tr>
                <th>Adresse :</th>
                <td><?= $utilisateur->getAdresse() ?></td>
            </tr>
            <tr>
                <th>Ville :</th>
                <td><?= $utilisateur->getVille() ?></td>
            </tr>
            <?php if ($utilisateur->getEstAdminBool()) { ?>
                <tr>
                    <th>Administrateur :</th>
                    <td>Oui</td>
                </tr>
            <?php } ?>
        </table>
        <div class="actions">
            <a href="controleurFrontal.php?action=afficherFormulaireMiseAJour&class=App\Controleur\Specifique\ControleurUtilisateur"
               class="btn">Modifier votre profil</a>
            <a href="controleurFrontal.php?action=afficherHistorique&class=App\Controleur\Specifique\ControleurUtilisateur"
               class="btn">Voir votre historique</a>
            <a href="controleurFrontal.php?action=deconnecter&class=App\Controleur\Specifique\ControleurUtilisateur"
               class="btn">Se déconnecter</a>
            <a
                    href="javascript:void(0);"
                    class="delete-button"
                    onclick="confirmSuppression('controleurFrontal.php?action=afficherSupprimer&class=App\\Controleur\\Specifique\\ControleurUtilisateur')">
                Supprimer votre compte
            </a></div>
    </section>
</main>

</body>
</html>
