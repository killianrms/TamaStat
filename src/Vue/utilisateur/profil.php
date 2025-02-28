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
        const mdp = document.getElementById("nouveau_mdp").value;
        const mdpConfirme = document.getElementById("confirmer_mdp").value;

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

    function verifierFormulaire() {
        const mdp = document.getElementById("nouveau_mdp").value;
        const mdpConfirme = document.getElementById("confirmer_mdp").value;

        const isMdpValide = document.querySelectorAll(".password-requirements .valid").length === 4;
        const isMdpConfirme = mdp === mdpConfirme && mdpConfirme.length > 0;

        document.getElementById("submitMdp").disabled = !(isMdpValide && isMdpConfirme);
    }

    function togglePassword(id) {
        const input = document.getElementById(id);
        if (input.type === "password") {
            input.type = "text";
        } else {
            input.type = "password";
        }
    }

</script>
</script>

</body>
</html>
