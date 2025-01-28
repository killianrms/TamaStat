<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un utilisateur</title>
    <script>
        function confirmerAjoutUtilisateur(form) {
            var role = form.role.value;
            if (role === 'admin') {
                var confirmation = confirm("Êtes-vous sûr de vouloir ajouter un utilisateur avec le rôle d'administrateur ? Cela peut être dangereux si ce n'est pas fait avec précaution.");
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

<h2>Ajouter un nouvel utilisateur</h2>

<form action="ajouterUtilisateur.php" method="POST" onsubmit="return confirmerAjoutUtilisateur(this)">
    <label for="nom_utilisateur">Nom d'utilisateur :</label>
    <input type="text" id="nom_utilisateur" name="nom_utilisateur" required><br>

    <label for="mot_de_passe">Mot de passe :</label>
    <input type="password" id="mot_de_passe" name="mot_de_passe" required><br>

    <label for="mot_de_passe_confirme">Confirmer le mot de passe :</label>
    <input type="password" id="mot_de_passe_confirme" name="mot_de_passe_confirme" required><br>

    <label for="email">Email :</label>
    <input type="email" id="email" name="email" required><br>

    <label for="role">Rôle :</label>
    <select id="role" name="role">
        <option value="utilisateur">Utilisateur</option>
        <option value="admin">Admin</option>
    </select><br>

    <button type="submit">Ajouter l'utilisateur</button>
</form>

</body>
</html>
