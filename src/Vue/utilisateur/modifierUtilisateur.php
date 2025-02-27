<?php
if (!isset($_SESSION['user']) || $_SESSION['user']['is_admin'] !== 1) {
    header('Location: routeur.php?route=connexion');
    exit;
}

use App\Configuration\ConnexionBD;

$id = $_GET['id'] ?? null;
if (!$id) {
    echo "ID utilisateur manquant.";
    exit;
}

$pdo = (new ConnexionBD())->getPdo();
$stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE id = :id');
$stmt->execute(['id' => $id]);
$utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$utilisateur) {
    echo "Utilisateur non trouvé.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_utilisateur = htmlspecialchars($_POST['nom_utilisateur']);
    $email = $_POST['email'];
    $is_admin = $_POST['is_admin'] ?? 0;

    $stmt = $pdo->prepare('UPDATE utilisateurs SET nom_utilisateur = :nom_utilisateur, email = :email, is_admin = :is_admin WHERE id = :id');
    $stmt->execute([
        ':nom_utilisateur' => $nom_utilisateur,
        ':email' => $email,
        ':is_admin' => $is_admin,
        ':id' => $id
    ]);

    header('Location: routeur.php?route=gestionUtilisateurs');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier l'utilisateur</title>
    <link rel="stylesheet" href="../../../ressources/css/style.css">
</head>
<body class="modifier-utilisateur-page">
<h1>Modifier l'utilisateur</h1>

<form method="POST" onsubmit="return verifierFormulaire()">
    <label for="nom_utilisateur">Nom d'utilisateur :</label>
    <input type="text" id="nom_utilisateur" name="nom_utilisateur"
           value="<?= htmlspecialchars($utilisateur['nom_utilisateur']) ?>" required><br>

    <label for="email">Email :</label>
    <input type="email" id="email" name="email" value="<?= htmlspecialchars($utilisateur['email']) ?>" required
           onkeyup="verifierEmail()">
    <p id="message-email" class="invalid">❌ Email invalide</p>

    <label for="is_admin">Administrateur :</label>
    <select id="is_admin" name="is_admin" onchange="verifierFormulaire()">
        <option value="0" <?= $utilisateur['is_admin'] == 0 ? 'selected' : '' ?>>Non</option>
        <option value="1" <?= $utilisateur['is_admin'] == 1 ? 'selected' : '' ?>>Oui</option>
    </select><br>

    <button type="submit" id="submitModif" disabled>Enregistrer les modifications</button>
</form>

<style>
    .invalid {
        color: red;
        font-weight: bold;
    }

    .valid {
        color: green;
        font-weight: bold;
    }
</style>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        verifierEmail();
    });

    function verifierEmail() {
        const emailInput = document.getElementById("email");
        const messageEmail = document.getElementById("message-email");
        const regexEmail = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;

        if (regexEmail.test(emailInput.value)) {
            messageEmail.classList.add("valid");
            messageEmail.classList.remove("invalid");
            messageEmail.innerHTML = "✔ Email valide";
        } else {
            messageEmail.classList.add("invalid");
            messageEmail.classList.remove("valid");
            messageEmail.innerHTML = "❌ Email invalide";
        }

        verifierFormulaire();
    }

    function verifierFormulaire() {
        const isEmailValide = document.getElementById("message-email").classList.contains("valid");
        const isAdmin = document.getElementById("is_admin").value === "1";

        const bouton = document.getElementById("submitModif");
        bouton.disabled = !isEmailValide;

        if (isAdmin && isEmailValide) {
            setTimeout(() => {
                if (!confirm("⚠️ Attention : Vous allez donner le rôle administrateur à cet utilisateur. Cette action est irréversible. Confirmez-vous ?")) {
                    bouton.disabled = true;
                    document.getElementById("is_admin").value = "0";
                }
            }, 50);
        }
    }

    document.getElementById("email").addEventListener("input", verifierEmail);
</script>

</body>
</html>
