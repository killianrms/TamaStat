<?php

use App\Configuration\ConnexionBD;
use App\Controleur\Specifique\ControleurCsv;

$connexion = new ConnexionBD();
$pdo = $connexion->getPdo();
$utilisateurId = $_SESSION['user']['id'];

$stmt = $pdo->prepare('SELECT COUNT(*) FROM box_types WHERE utilisateur_id = ?');
$stmt->execute([$utilisateurId]);
$hasBoxes = $stmt->fetchColumn() > 0;

$stmt = $pdo->prepare('SELECT COUNT(*) FROM utilisateur_boxes WHERE utilisateur_id = ?');
$stmt->execute([$utilisateurId]);
$hasBoxesConfig = $stmt->fetchColumn() > 0;

$stmt = $pdo->prepare('SELECT COUNT(*) FROM locations WHERE utilisateur_id = ?');
$stmt->execute([$utilisateurId]);
$hasContrats = $stmt->fetchColumn() > 0;

$stmt = $pdo->prepare('SELECT COUNT(*) FROM factures WHERE utilisateur_id = ?');
$stmt->execute([$utilisateurId]);
$hasFactures = $stmt->fetchColumn() > 0;

$succes = $erreur = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controleurCsv = new ControleurCsv();

    if (isset($_FILES['csv_box']) && $_FILES['csv_box']['size'] > 0) {
        try {
            $controleurCsv->importerBoxTypes($_FILES['csv_box']);
            $succes = "Fichier CSV des box importé avec succès.";
        } catch (Exception $e) {
            $erreur = "Erreur : " . $e->getMessage();
        }
    }

    if (isset($_FILES['csv_contrats']) && $_FILES['csv_contrats']['size'] > 0) {
        try {
            $controleurCsv->importerContrats($_FILES['csv_contrats'], $utilisateurId);
            $succes = "Fichier CSV des contrats importé avec succès.";
        } catch (Exception $e) {
            $erreur = "Erreur : " . $e->getMessage();
        }
    }

    if (isset($_FILES['csv_factures']) && $_FILES['csv_factures']['size'] > 0) {
        try {
            $controleurCsv->importerFactures($_FILES['csv_factures'], $utilisateurId);
            $succes = "Fichier CSV des factures importé avec succès.";
        } catch (Exception $e) {
            $erreur = "Erreur : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil</title>
    <link rel="stylesheet" href="../ressources/css/style.css">
</head>
<body class="profil-page">
<h1>Profil</h1>
<?php if (!empty($_SESSION['erreur_message'])): ?>
    <div class="error-message"><?= htmlspecialchars($_SESSION['erreur_message']) ?></div>
    <?php unset($_SESSION['erreur_message']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['succes_message'])): ?>
    <div class="success-message"><?= htmlspecialchars($_SESSION['succes_message']) ?></div>
    <?php unset($_SESSION['succes_message']); ?>
<?php endif; ?>

<!-- Étape Changer le mot de passe -->
<div class="etape-card">
    <h3 class="etape-title">Changer le mot de passe</h3>
    <form action="routeur.php?route=changer-mdp" method="POST">
        <label for="ancien_mdp">Ancien mot de passe :</label>
        <div class="password-container">
            <input type="password" id="ancien_mdp" name="ancien_mdp" required>
            <span class="toggle-password" onclick="togglePassword('ancien_mdp')">👁️</span>
        </div>

        <label for="nouveau_mdp">Nouveau mot de passe :</label>
        <div class="password-container">
            <input type="password" id="nouveau_mdp" name="nouveau_mdp" required onkeyup="verifierMdp()">
            <span class="toggle-password" onclick="togglePassword('nouveau_mdp')">👁️</span>
        </div>

        <ul class="password-requirements">
            <li id="min8" class="invalid">❌ Au moins 8 caractères</li>
            <li id="majuscule" class="invalid">❌ Une majuscule</li>
            <li id="chiffre" class="invalid">❌ Un chiffre</li>
            <li id="special" class="invalid">❌ Un caractère spécial (!@#$%^&*)</li>
        </ul>

        <label for="confirmer_mdp">Confirmer le nouveau mot de passe :</label>
        <div class="password-container">
            <input type="password" id="confirmer_mdp" name="confirmer_mdp" required onkeyup="verifierMdp()">
            <span class="toggle-password" onclick="togglePassword('confirmer_mdp')">👁️</span>
        </div>

        <p id="message-confirmation" class="invalid">❌ Les mots de passe ne correspondent pas</p>

        <button type="submit" id="submitMdp" disabled>Changer le mot de passe</button>
    </form>
</div>

<!-- Étape 1 : Import des box -->
<div class="etape-card">
    <h3 class="etape-title">Étape 1 : Import des box</h3>
    <p><?= $hasBoxes ? 'Données importées' : 'Données non importées' ?></p>
    <form action="routeur.php?route=profil" method="POST" enctype="multipart/form-data">
        <input type="file" name="csv_box" accept=".csv">
        <button type="submit"><?= $hasBoxes ? 'Réimporter' : 'Importer' ?></button>
    </form>
</div>

<!-- Étape 2 : Configuration des box -->
<div class="etape-card <?= $hasBoxesConfig ? 'etape-complete' : '' ?>">
    <h3 class="etape-title">Étape 2 : Configuration des box</h3>
    <p><?= $hasBoxesConfig ? 'Configuration effectuée' : 'Configuration non effectuée' ?></p>
    <?php if ($hasBoxesConfig): ?>
        <a href="routeur.php?route=modifier-boxes" class="btn">Modifier la configuration</a>
    <?php endif; ?>
</div>

<!-- Étape 3 : Import des contrats -->
<div class="etape-card">
    <h3 class="etape-title">Étape 3 : Import des contrats</h3>
    <p><?= $hasContrats ? 'Données importées' : 'Données non importées' ?></p>
    <form action="routeur.php?route=profil" method="POST" enctype="multipart/form-data">
        <input type="file" name="csv_contrats" accept=".csv">
        <button type="submit"><?= $hasContrats ? 'Réimporter' : 'Importer' ?></button>
    </form>
</div>

<!-- Étape 4 : Import des factures -->
<div class="etape-card">
    <h3 class="etape-title">Étape 4 : Import des factures</h3>
    <p><?= $hasFactures ? 'Données importées' : 'Données non importées' ?></p>
    <form action="routeur.php?route=profil" method="POST" enctype="multipart/form-data">
        <input type="file" name="csv_factures" accept=".csv">
        <button type="submit"><?= $hasFactures ? 'Réimporter' : 'Importer' ?></button>
    </form>
</div>

<script>
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
</script>

</body>
</html>
