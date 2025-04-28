<?php
if (!isset($_SESSION['user']) || $_SESSION['user']['is_admin'] !== 1) {
    header('Location: routeur.php?route=connexion');
    exit;
}

use App\Configuration\ConnexionBD;

$pdo = (new ConnexionBD())->getPdo();
$stmt = $pdo->query('SELECT id, nom_utilisateur, email, is_admin, is_locked FROM utilisateurs');
$utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des utilisateurs</title>
    <link rel="stylesheet" href="../../../ressources/css/style.css">
</head>
<body class="gestion-utilisateurs-page">
<h1>Gestion des utilisateurs</h1>

<table class="user-table">
    <thead>
    <tr>
        <th>Nom d'utilisateur</th>
        <th>Email</th>
        <th>Admin</th>
        <th>Statut</th>
        <th>Actions</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($utilisateurs as $utilisateur): ?>
        <tr>
            <td><?= htmlspecialchars($utilisateur['nom_utilisateur']) ?></td>
            <td><?= htmlspecialchars($utilisateur['email']) ?></td>
            <td><?= $utilisateur['is_admin'] ? 'Oui' : 'Non' ?></td>
            <td><?= $utilisateur['is_locked'] ? 'Verrouillé' : 'Actif' ?></td>
            <td class="actions">
                <?php // Prevent actions on the logged-in user ?>
                <?php if ($utilisateur['id'] === $_SESSION['user']['id']): ?>
                    <p>Action impossible sur soi-même</p>
                <?php // Prevent modification/deletion of other admins, but allow locking/unlocking ?>
                <?php elseif ($utilisateur['is_admin']): ?>
                    <a href="routeur.php?route=toggleLockStatus&id=<?= $utilisateur['id'] ?>">
                        <?= $utilisateur['is_locked'] ? 'Déverrouiller' : 'Verrouiller' ?>
                    </a>
                    <p>Modification/Suppression impossible (Admin)</p>
                <?php // Allow all actions for non-admin users ?>
                <?php else: ?>
                    <a href="routeur.php?route=modifierUtilisateur&id=<?= $utilisateur['id'] ?>">Modifier</a>
                    <a href="routeur.php?route=supprimerUtilisateur&id=<?= $utilisateur['id'] ?>" class="delete-link" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')">Supprimer</a>
                    <a href="routeur.php?route=toggleLockStatus&id=<?= $utilisateur['id'] ?>">
                        <?= $utilisateur['is_locked'] ? 'Déverrouiller' : 'Verrouiller' ?>
                    </a>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<a href="routeur.php?route=ajouterUtilisateur" class="add-user-button">Ajouter un utilisateur</a>
</body>
</html>