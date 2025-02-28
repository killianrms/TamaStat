<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un utilisateur</title>
    <link rel="stylesheet" href="../../../ressources/css/style.css">
</head>
<body class="ajouter-utilisateur-page">
<h1>Ajouter un nouvel utilisateur</h1>

<form action="routeur.php?route=ajouterUtilisateur" method="POST" onsubmit="return verifierFormulaire()">
    <label for="nom_utilisateur">Nom d'utilisateur :</label>
    <input type="text" id="nom_utilisateur" name="nom_utilisateur" required><br>

    <label for="mot_de_passe">Mot de passe :</label>
    <div class="password-container">
        <input type="password" id="mot_de_passe" name="mot_de_passe" required onkeyup="verifierMdp()">
        <span class="toggle-password" onclick="togglePassword('mot_de_passe')">👁️</span>
    </div>

    <ul class="password-requirements">
        <li id="min8" class="invalid">❌ Au moins 8 caractères</li>
        <li id="majuscule" class="invalid">❌ Une majuscule</li>
        <li id="chiffre" class="invalid">❌ Un chiffre</li>
        <li id="special" class="invalid">❌ Un caractère spécial (!@#$%^&*)</li>
    </ul>

    <label for="mot_de_passe_confirme">Confirmer le mot de passe :</label>
    <div class="password-container">
        <input type="password" id="mot_de_passe_confirme" name="mot_de_passe_confirme" required onkeyup="verifierMdp()">
        <span class="toggle-password" onclick="togglePassword('mot_de_passe_confirme')">👁️</span>
    </div>

    <p id="message-confirmation" class="invalid">❌ Les mots de passe ne correspondent pas</p>

    <label for="email">Email :</label>
    <input type="email" id="email" name="email" required onkeyup="verifierEmail()">
    <p id="message-email" class="invalid">❌ Email invalide</p>

    <label for="is_admin">Administrateur :</label>
    <select id="is_admin" name="is_admin">
        <option value="0">Non</option>
        <option value="1">Oui</option>
    </select><br>

    <button type="submit" id="submitUtilisateur" disabled>Ajouter l'utilisateur</button>
</form>

<script>
    function updateRequirement(element, condition, texteValide, texteInvalide) {
        if (condition) {
            element.classList.add("valid");
            element.classList.remove("invalid");
            element.innerHTML = "✔ " + texteValide;
        } else {
            element.classList.add("invalid");
            element.classList.remove("valid");
            element.innerHTML = "❌ " + texteInvalide;
        }
    }

    function verifierMdp() {
        const mdp = document.getElementById("mot_de_passe").value;
        const mdpConfirme = document.getElementById("mot_de_passe_confirme").value;

        updateRequirement(
            document.getElementById("min8"),
            mdp.length >= 8,
            "Au moins 8 caractères",
            "Au moins 8 caractères"
        );

        updateRequirement(
            document.getElementById("majuscule"),
            /[A-Z]/.test(mdp),
            "Une majuscule",
            "Une majuscule"
        );

        updateRequirement(
            document.getElementById("chiffre"),
            /[0-9]/.test(mdp),
            "Un chiffre",
            "Un chiffre"
        );

        updateRequirement(
            document.getElementById("special"),
            /[!@#$%^&*]/.test(mdp),
            "Un caractère spécial (!@#$%^&*)",
            "Un caractère spécial (!@#$%^&*)"
        );

        const messageConfirmation = document.getElementById("message-confirmation");

        if (mdpConfirme.length > 0) {
            if (mdp === mdpConfirme) {
                messageConfirmation.innerHTML = "✔ Les mots de passe correspondent";
                messageConfirmation.classList.add("valid");
                messageConfirmation.classList.remove("invalid");
            } else {
                messageConfirmation.innerHTML = "❌ Les mots de passe ne correspondent pas";
                messageConfirmation.classList.add("invalid");
                messageConfirmation.classList.remove("valid");
            }
        } else {
            messageConfirmation.innerHTML = "❌ Les mots de passe ne correspondent pas";
            messageConfirmation.classList.remove("valid");
            messageConfirmation.classList.add("invalid");
        }

        verifierFormulaire();
    }

    function verifierEmail() {
        const email = document.getElementById("email").value;
        const messageEmail = document.getElementById("message-email");
        const regexEmail = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;

        if (regexEmail.test(email)) {
            messageEmail.innerHTML = "✔ Email valide";
            messageEmail.classList.add("valid");
            messageEmail.classList.remove("invalid");
        } else {
            messageEmail.innerHTML = "❌ Email invalide";
            messageEmail.classList.add("invalid");
            messageEmail.classList.remove("valid");
        }

        verifierFormulaire();
    }

    function verifierFormulaire() {
        const mdp = document.getElementById("mot_de_passe").value;
        const mdpConfirme = document.getElementById("mot_de_passe_confirme").value;

        const isMdpValide = document.querySelectorAll(".password-requirements .valid").length === 4;
        const isMdpConfirme = mdp === mdpConfirme && mdpConfirme.length > 0;
        const isEmailValide = document.getElementById("message-email").classList.contains("valid");

        document.getElementById("submitUtilisateur").disabled = !(isMdpValide && isMdpConfirme && isEmailValide);
    }

</script>
</body>
</html>
