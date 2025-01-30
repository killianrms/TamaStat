<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un utilisateur</title>
    <script>
        function confirmerAjoutUtilisateur(form) {
            var isAdmin = form.is_admin.value;
            if (isAdmin === '1') {
                var confirmation = confirm("Êtes-vous sûr de vouloir ajouter un utilisateur avec le rôle d'administrateur ?");
                if (!confirmation) {
                    return false;
                }
            }

            var mdp = form.mot_de_passe.value;
            var mdpConfirme = form.mot_de_passe_confirme.value;

            var regexMdp = /^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*])[A-Za-z\d!@#$%^&*]{8,}$/;
            if (!regexMdp.test(mdp)) {
                alert("Le mot de passe doit comporter au moins 8 caractères, une majuscule, un chiffre et un caractère spécial.");
                return false;
            }

            if (mdp !== mdpConfirme) {
                alert("Les mots de passe ne correspondent pas.");
                return false;
            }

            var email = form.email.value;
            var regexEmail = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
            if (!regexEmail.test(email)) {
                alert("Veuillez entrer un email valide.");
                return false;
            }

            return true;
        }
    </script>
</head>
<body>

<h1>Ajouter un nouvel utilisateur</h1>

<?php
if (isset($_SESSION['erreurs']) && !empty($_SESSION['erreurs'])):
    echo '<div style="color: red;">';
    foreach ($_SESSION['erreurs'] as $erreur) {
        echo "<p>$erreur</p>";
    }
    echo '</div>';
    unset($_SESSION['erreurs']);
endif;
?>

<form action="routeur.php?route=ajouterUtilisateur" method="POST" onsubmit="return confirmerAjoutUtilisateur(this)">
    <label for="nom_utilisateur">Nom d'utilisateur :</label>
    <input type="text" id="nom_utilisateur" name="nom_utilisateur" value="<?= isset($_POST['nom_utilisateur']) ? htmlspecialchars($_POST['nom_utilisateur']) : '' ?>" required><br>

    <label for="mot_de_passe">Mot de passe :</label>
    <input type="password" id="mot_de_passe" name="mot_de_passe" required><br>

    <label for="mot_de_passe_confirme">Confirmer le mot de passe :</label>
    <input type="password" id="mot_de_passe_confirme" name="mot_de_passe_confirme" required><br>

    <label for="email">Email :</label>
    <input type="email" id="email" name="email" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required><br>

    <label for="is_admin">Administrateur :</label>
    <select id="is_admin" name="is_admin">
        <option value="0" <?= (isset($_POST['is_admin']) && $_POST['is_admin'] == '0') ? 'selected' : '' ?>>Non</option>
        <option value="1" <?= (isset($_POST['is_admin']) && $_POST['is_admin'] == '1') ? 'selected' : '' ?>>Oui</option>
    </select><br>

    <button type="submit">Ajouter l'utilisateur</button>
</form>

</body>
</html>
