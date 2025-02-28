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

<style>
    .password-requirements {
        list-style: none;
        padding: 0;
    }

    .password-requirements li, #message-confirmation, #message-email {
        font-size: 0.9rem;
        margin-bottom: 5px;
        font-weight: bold;
    }

    .invalid {
        color: red;
    }

    .valid {
        color: green;
    }
</style>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        verifierMdp();
    });

    function togglePassword(id) {
        const input = document.getElementById(id);
        const icon = document.querySelector(`#${id} + .toggle-password`);
        if (input.type === "password") {
            input.type = "text";
            icon.innerHTML = "🙈"; // Icône fermée
        } else {
            input.type = "password";
            icon.innerHTML = "👁️"; // Icône ouverte
        }
    }

    function updateRequirement(element, condition) {
        if (condition) {
            element.classList.add("valid");
            element.classList.remove("invalid");
            element.innerHTML = "✔ " + element.innerHTML.slice(2);
        } else {
            element.classList.add("invalid");
            element.classList.remove("valid");
            element.innerHTML = "❌ " + element.innerHTML.slice(2);
        }
    }

    function verifierMdp() {
        const mdp = document.getElementById("mot_de_passe").value;
        const mdpConfirme = document.getElementById("mot_de_passe_confirme").value;
        const min8 = document.getElementById("min8");
        const majuscule = document.getElementById("majuscule");
        const chiffre = document.getElementById("chiffre");
        const special = document.getElementById("special");
        const messageConfirmation = document.getElementById("message-confirmation");
        const bouton = document.getElementById("submitUtilisateur");

        const regMajuscule = /[A-Z]/;
        const regChiffre = /[0-9]/;
        const regSpecial = /[!@#$%^&*]/;

        updateRequirement(min8, mdp.length >= 8);
        updateRequirement(majuscule, regMajuscule.test(mdp));
        updateRequirement(chiffre, regChiffre.test(mdp));
        updateRequirement(special, regSpecial.test(mdp));

        if (mdpConfirme.length > 0) {
            updateRequirement(messageConfirmation, mdp === mdpConfirme);
        } else {
            messageConfirmation.classList.remove("valid", "invalid");
            messageConfirmation.innerHTML = "❌ Les mots de passe ne correspondent pas";
        }

        const mdpValide = mdp.length >= 8 && regMajuscule.test(mdp) && regChiffre.test(mdp) && regSpecial.test(mdp);
        const mdpConfirmeValide = mdp === mdpConfirme && mdpConfirme.length > 0;

        bouton.disabled = !(mdpValide && mdpConfirmeValide);
    }

    function verifierConfirmationMdp() {
        const mdp = document.getElementById("mot_de_passe").value;
        const mdpConfirme = document.getElementById("mot_de_passe_confirme").value;
        const messageConfirmation = document.getElementById("message-confirmation");

        if (mdpConfirme.length > 0) {
            updateRequirement(messageConfirmation, mdp === mdpConfirme);
        } else {
            messageConfirmation.classList.remove("valid", "invalid");
            messageConfirmation.innerHTML = "❌ Les mots de passe ne correspondent pas";
        }

        verifierFormulaire();
    }

    function verifierEmail() {
        const email = document.getElementById("email").value;
        const messageEmail = document.getElementById("message-email");
        const regexEmail = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;

        updateRequirement(messageEmail, regexEmail.test(email));

        verifierFormulaire();
    }

    function verifierFormulaire() {
        const mdp = document.getElementById("mot_de_passe").value;
        const mdpConfirme = document.getElementById("mot_de_passe_confirme").value;
        const email = document.getElementById("email").value;
        const isMdpValide = document.querySelectorAll(".password-requirements .valid").length === 4;
        const isMdpConfirme = mdp === mdpConfirme && mdpConfirme.length > 0;
        const isEmailValide = document.getElementById("message-email").classList.contains("valid");

        document.getElementById("submitUtilisateur").disabled = !(isMdpValide && isMdpConfirme && isEmailValide);
    }
</script>
</body>
</html>
