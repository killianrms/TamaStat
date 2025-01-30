<?php
if (!isset($_SESSION['user']) || $_SESSION['user']['is_admin'] !== 1) {
    echo "Accès non autorisé!";
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

<h2>Modifier l'utilisateur</h2>

<form method="POST">
    <label for="nom_utilisateur">Nom d'utilisateur :</label>
    <input type="text" id="nom_utilisateur" name="nom_utilisateur" value="<?= htmlspecialchars($utilisateur['nom_utilisateur']) ?>" required><br>

    <label for="email">Email :</label>
    <input type="email" id="email" name="email" value="<?= htmlspecialchars($utilisateur['email']) ?>" required><br>

    <label for="is_admin">Administrateur :</label>
    <select id="is_admin" name="is_admin">
        <option value="0" <?= $utilisateur['is_admin'] == 0 ? 'selected' : '' ?>>Non</option>
        <option value="1" <?= $utilisateur['is_admin'] == 1 ? 'selected' : '' ?>>Oui</option>
    </select><br>

    <button type="submit">Enregistrer les modifications</button>
</form>