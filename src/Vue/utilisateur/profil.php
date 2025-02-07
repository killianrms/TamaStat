<?php
require_once __DIR__ . '/../../Configuration/ConnexionBD.php';

$connexion = new ConnexionBD();
$pdo = $connexion->getPdo();

$utilisateurId = $_SESSION['user']['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['changer_mdp'])) {
        $ancienMdp = $_POST['ancien_mdp'];
        $nouveauMdp = $_POST['nouveau_mdp'];
        $confirmerMdp = $_POST['confirmer_mdp'];

        if ($nouveauMdp !== $confirmerMdp) {
            $erreur = "Les mots de passe ne correspondent pas.";
        } else {
            $stmt = $pdo->prepare('SELECT mot_de_passe FROM utilisateurs WHERE id = ?');
            $stmt->execute([$utilisateurId]);
            $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

            if (password_verify($ancienMdp, $utilisateur['mot_de_passe'])) {
                $nouveauMdpHash = password_hash($nouveauMdp, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?');
                $stmt->execute([$nouveauMdpHash, $utilisateurId]);
                $succes = "Mot de passe changé avec succès.";
            } else {
                $erreur = "Ancien mot de passe incorrect.";
            }
        }
    } elseif (isset($_FILES['csv_box'])) {
        $controleurCsv = new ControleurCsv();
        try {
            $controleurCsv->importerBoxTypes($_FILES['csv_box']);
            $succes = "Fichier CSV des box importé avec succès.";
        } catch (Exception $e) {
            $erreur = "Erreur : " . $e->getMessage();
        }
    } elseif (isset($_FILES['csv_contrats'])) {
        $controleurCsv = new ControleurCsv();
        try {
            $controleurCsv->importerContrats($_FILES['csv_contrats'], $utilisateurId);
            $succes = "Fichier CSV des contrats importé avec succès.";
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
</head>
<body class="profil-page">
<h1>Profil</h1>

<?php if (isset($erreur)): ?>
    <div class="error-message"><?= htmlspecialchars($erreur) ?></div>
<?php endif; ?>

<?php if (isset($succes)): ?>
    <div class="success-message"><?= htmlspecialchars($succes) ?></div>
<?php endif; ?>

<h2>Changer le mot de passe</h2>
<form action="routeur.php?route=profil" method="POST">
    <label for="ancien_mdp">Ancien mot de passe :</label>
    <input type="password" id="ancien_mdp" name="ancien_mdp" required><br>

    <label for="nouveau_mdp">Nouveau mot de passe :</label>
    <input type="password" id="nouveau_mdp" name="nouveau_mdp" required><br>

    <label for="confirmer_mdp">Confirmer le nouveau mot de passe :</label>
    <input type="password" id="confirmer_mdp" name="confirmer_mdp" required><br>

    <button type="submit" name="changer_mdp">Changer le mot de passe</button>
</form>

<h2>Importer de nouveaux fichiers CSV</h2>
<form action="routeur.php?route=profil" method="POST" enctype="multipart/form-data">
    <label for="csv_box">Importer un fichier CSV des box :</label>
    <input type="file" id="csv_box" name="csv_box" accept=".csv"><br>

    <label for="csv_contrats">Importer un fichier CSV des contrats :</label>
    <input type="file" id="csv_contrats" name="csv_contrats" accept=".csv"><br>

    <button type="submit">Importer</button>
</form>
</body>
</html>