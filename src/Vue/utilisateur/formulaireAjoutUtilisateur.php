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
    <select id="is_admin" name="is_admin" onchange="verifierFormulaire()">
        <option value="0">Non</option>
        <option value="1">Oui</option>
    </select><br>

    <button type="submit" id="submitUtilisateur" disabled>Ajouter l'utilisateur</button>
</form>

<style>
    .password-container {
        position: relative;
        width: fit-content;
    }

    .password-container input {
        padding-right: 30px;
    }

    .password-container .toggle-password {
        position: absolute;
        right: 5px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
    }

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
    function togglePassword(id) {
        const input = document.getElementById(id);
        if (input.type === "password") {
            input.type = "text";
        } else {
            input.type = "password";
        }
    }

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

        updateRequirement(min8, mdp.length >= 8, "Au moins 8 caractères", "Au moins 8 caractères");
        updateRequirement(majuscule, /[A-Z]/.test(mdp), "Une majuscule", "Une majuscule");
        updateRequirement(chiffre, /[0-9]/.test(mdp), "Un chiffre", "Un chiffre");
        updateRequirement(special, /[!@#$%^&*]/.test(mdp), "Un caractère spécial (!@#$%^&*)", "Un caractère spécial (!@#$%^&*)");

        verifierConfirmationMdp();
    }

    function verifierConfirmationMdp() {
        const mdp = document.getElementById("mot_de_passe").value;
        const mdpConfirme = document.getElementById("mot_de_passe_confirme").value;
        const messageConfirmation = document.getElementById("message-confirmation");

        updateRequirement(messageConfirmation, mdp === mdpConfirme, "Les mots de passe correspondent", "Les mots de passe ne correspondent pas");

        verifierFormulaire();
    }

    function verifierEmail() {
        const email = document.getElementById("email").value;
        const messageEmail = document.getElementById("message-email");
        const regexEmail = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;

        updateRequirement(messageEmail, regexEmail.test(email), "Email valide", "Email invalide");

        verifierFormulaire();
    }

    function verifierFormulaire() {
        const isMdpValide = document.querySelectorAll(".password-requirements .valid").length === 4;
        const isMdpConfirme = document.getElementById("message-confirmation").classList.contains("valid");
        const isEmailValide = document.getElementById("message-email").classList.contains("valid");
        const isAdmin = document.getElementById("is_admin").value === "1";

        const bouton = document.getElementById("submitUtilisateur");

        bouton.disabled = !(isMdpValide && isMdpConfirme && isEmailValide);

        if (isAdmin && !bouton.disabled) {
            setTimeout(() => {
                if (!confirm("⚠️ Vous allez créer un administrateur ! Cette action est irréversible. Confirmer ?")) {
                    bouton.disabled = true;
                }
            }, 100);
        }
    }
</script>

</body>
</html>
