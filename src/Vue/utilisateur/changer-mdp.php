<?php
USE App\Configuration\ConnexionBD;

$connexion = new ConnexionBD();
$pdo = $connexion->getPdo();

$utilisateurId = $_SESSION['user']['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ancienMdp = $_POST['ancien_mdp'];
    $nouveauMdp = $_POST['nouveau_mdp'];
    $confirmerMdp = $_POST['confirmer_mdp'];

    // Vérifier l'ancien mot de passe
    $stmt = $pdo->prepare('SELECT mot_de_passe FROM utilisateurs WHERE id = ?');
    $stmt->execute([$utilisateurId]);
    $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

    if (password_verify($ancienMdp, $utilisateur['mot_de_passe'])) {
        if ($nouveauMdp === $confirmerMdp) {
            $nouveauMdpHash = password_hash($nouveauMdp, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?');
            $stmt->execute([$nouveauMdpHash, $utilisateurId]);
        }
    }
    header('Location: routeur.php?route=profil');
    exit;
}
?>
