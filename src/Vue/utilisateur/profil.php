<?php

use App\Configuration\ConnexionBD;
use App\Controleur\Specifique\ControleurCsv;

$connexion = new ConnexionBD();
$pdo = $connexion->getPdo();
$utilisateurId = $_SESSION['user']['id'];

// Récupération de la dernière modification du mot de passe
$stmt = $pdo->prepare('SELECT date_dernier_changement_mdp FROM utilisateurs WHERE id = ?');
$stmt->execute([$utilisateurId]);
$dateDernierMdp = $stmt->fetchColumn();

// Récupération des dates d'import depuis `import_tracking`
$tables = ['box_types', 'utilisateur_boxes', 'locations', 'factures', 'recap_ventes'];
$dateDerniersImports = [];

foreach ($tables as $table) {
    $stmt = $pdo->prepare('SELECT MAX(date_dernier_import) FROM import_tracking WHERE utilisateur_id = ? AND table_name = ?');
    $stmt->execute([$utilisateurId, $table]);
    $dateDerniersImports[$table] = $stmt->fetchColumn();
}

$succes = $erreur = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controleurCsv = new ControleurCsv();

    if (isset($_FILES['csv_box']) && $_FILES['csv_box']['size'] > 0) {
        try {
            $controleurCsv->importerBoxTypes($_FILES['csv_box']);
            $pdo->prepare('INSERT INTO import_tracking (utilisateur_id, table_name, date_dernier_import) VALUES (?, ?, NOW()) 
                          ON DUPLICATE KEY UPDATE date_dernier_import = NOW()')->execute([$utilisateurId, 'box_types']);
            $succes = "Fichier CSV des box importé avec succès.";
        } catch (Exception $e) {
            $erreur = "Erreur : " . $e->getMessage();
        }
    }

    if (isset($_FILES['csv_contrats']) && $_FILES['csv_contrats']['size'] > 0) {
        try {
            $controleurCsv->importerContrats($_FILES['csv_contrats'], $utilisateurId);
            $pdo->prepare('INSERT INTO import_tracking (utilisateur_id, table_name, date_dernier_import) VALUES (?, ?, NOW()) 
                          ON DUPLICATE KEY UPDATE date_dernier_import = NOW()')->execute([$utilisateurId, 'locations']);
            $succes = "Fichier CSV des contrats importé avec succès.";
        } catch (Exception $e) {
            $erreur = "Erreur : " . $e->getMessage();
        }
    }

    if (isset($_FILES['csv_factures']) && $_FILES['csv_factures']['size'] > 0) {
        try {
            $controleurCsv->importerFactures($_FILES['csv_factures'], $utilisateurId);
            $pdo->prepare('INSERT INTO import_tracking (utilisateur_id, table_name, date_dernier_import) VALUES (?, ?, NOW()) 
                          ON DUPLICATE KEY UPDATE date_dernier_import = NOW()')->execute([$utilisateurId, 'factures']);
            $succes = "Fichier CSV des factures importé avec succès.";
        } catch (Exception $e) {
            $erreur = "Erreur : " . $e->getMessage();
        }
    }

    if (isset($_FILES['csv_recap_ventes']) && $_FILES['csv_recap_ventes']['size'] > 0) {
        try {
            $controleurCsv->importerRecapVentes($_FILES['csv_recap_ventes'], $utilisateurId);
            $pdo->prepare('INSERT INTO import_tracking (utilisateur_id, table_name, date_dernier_import) VALUES (?, ?, NOW()) 
                          ON DUPLICATE KEY UPDATE date_dernier_import = NOW()')->execute([$utilisateurId, 'recap_ventes']);
            $succes = "Fichier CSV des recap ventes importé avec succès.";
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
    <title>Profil</title>
    <link rel="stylesheet" href="../../../ressources/js/script.js">
</head>
<body>
<h1>Profil</h1>
<div class="profil-container">
<div class="etape-card">
    <h3 class="etape-title">Changer de mot de passe</h3>
    <p>Dernier changement
        : <?= $dateDernierMdp ? date('d/m/Y H:i', strtotime($dateDernierMdp)) : 'Jamais' ?></p>
    <form action="routeur.php?route=changer-mdp" method="POST">
        <label for="ancien_mdp">Ancien mot de passe :</label>
        <div class="password-container">
            <input type="password" id="ancien_mdp" name="ancien_mdp" required>
            <span class="toggle-password" onclick="togglePassword('ancien_mdp')">
            <img src="../../../ressources/images/oeil-ferme.png" alt="Oeil fermé" id="oeil-ferme-ancien">
            <img src="../../../ressources/images/oeil-ouvert.png" alt="Oeil ouvert" id="oeil-ouvert-ancien"
                 style="display: none;">
        </span>
        </div>

        <label for="nouveau_mdp">Nouveau mot de passe :</label>
        <div class="password-container">
            <input type="password" id="nouveau_mdp" name="nouveau_mdp" required onkeyup="verifierMdp()">
            <span class="toggle-password" onclick="togglePassword('nouveau_mdp')">
            <img src="../../../ressources/images/oeil-ferme.png" alt="Oeil fermé" id="oeil-ferme-nouveau">
            <img src="../../../ressources/images/oeil-ouvert.png" alt="Oeil ouvert" id="oeil-ouvert-nouveau"
                 style="display: none;">
        </span>
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
            <span class="toggle-password" onclick="togglePassword('confirmer_mdp')">
            <img src="../../../ressources/images/oeil-ferme.png" alt="Oeil fermé" id="oeil-ferme-confirmer">
            <img src="../../../ressources/images/oeil-ouvert.png" alt="Oeil ouvert" id="oeil-ouvert-confirmer"
                 style="display: none;">
        </span>
        </div>

        <p id="message-confirmation" class="invalid">❌ Les mots de passe ne correspondent pas</p>

        <button type="submit" id="submitMdp" disabled>Changer le mot de passe</button>
    </form>
</div>

<div class="etape-card">
    <h3>Import des box</h3>
    <p>Dernier import
        : <?= isset($dateDerniersImports['box_types']) ? date('d/m/Y H:i', strtotime($dateDerniersImports['box_types'])) : 'Jamais' ?></p>
    <form action="routeur.php?route=profil" method="POST" enctype="multipart/form-data" onsubmit="showLoader(this)">
        <input type="file" name="csv_box" accept=".csv">
        <button type="submit">Importer</button>
        <div class="loader"></div>
    </form>
</div>

<div class="etape-card">
    <h3>Modifier la quantité de boxes</h3>
    <p>Dernier import
        : <?= isset($dateDerniersImports['utilisateur_boxes']) ? date('d/m/Y H:i', strtotime($dateDerniersImports['utilisateur_boxes'])) : 'Jamais' ?></p>
    <a href="routeur.php?route=modifier-boxes" class="btn">Modifier la configuration</a>
</div>

<div class="etape-card">
    <h3>Import des contrats</h3>
    <p>Dernier import
        : <?= isset($dateDerniersImports['locations']) ? date('d/m/Y H:i', strtotime($dateDerniersImports['locations'])) : 'Jamais' ?></p>
    <form action="routeur.php?route=profil" method="POST" enctype="multipart/form-data" onsubmit="showLoader(this)">
        <input type="file" name="csv_contrats" accept=".csv">
        <button type="submit">Importer</button>
        <div class="loader"></div>
    </form>
</div>

<div class="etape-card">
    <h3>Import des factures</h3>
    <p>Dernier import
        : <?= isset($dateDerniersImports['factures']) ? date('d/m/Y H:i', strtotime($dateDerniersImports['factures'])) : 'Jamais' ?></p>
    <form action="routeur.php?route=profil" method="POST" enctype="multipart/form-data" onsubmit="showLoader(this)">
        <input type="file" name="csv_factures" accept=".csv">
        <button type="submit">Importer</button>
        <div class="loader"></div>
    </form>
</div>

<div class="etape-card">
    <h3>Import des recap ventes</h3>
    <p>Dernier import
        : <?= isset($dateDerniersImports['recap_ventes']) ? date('d/m/Y H:i', strtotime($dateDerniersImports['recap_ventes'])) : 'Jamais' ?></p>
    <form action="routeur.php?route=profil" method="POST" enctype="multipart/form-data" onsubmit="showLoader(this)">
        <input type="file" name="csv_recap_ventes" accept=".csv">
        <button type="submit">Importer</button>
        <div class="loader"></div>
    </form>
</div>
</div>
<script>
    function showLoader(form) {
        const button = form.querySelector('button[type="submit"]');
        const loader = form.querySelector('.loader');
        button.disabled = true;
        loader.style.display = 'block';

        setTimeout(() => {
            button.disabled = false;
            loader.style.display = 'none';
        }, 300000);
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
        const eyeIconOpen = input.nextElementSibling.querySelector('img[id^="oeil-ouvert"]');
        const eyeIconClosed = input.nextElementSibling.querySelector('img[id^="oeil-ferme"]');

        if (input.type === "password") {
            input.type = "text";
            eyeIconOpen.style.display = "inline";
            eyeIconClosed.style.display = "none";
        } else {
            input.type = "password";
            eyeIconOpen.style.display = "none";
            eyeIconClosed.style.display = "inline";
        }
    }
</script>
</body>
</html>
