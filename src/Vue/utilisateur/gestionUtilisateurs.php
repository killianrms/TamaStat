<?php
if (!isset($_SESSION['user']) || $_SESSION['user']['is_admin'] !== 1) {
    echo "Accès non autorisé!";
    exit;
}

use App\Configuration\ConnexionBD;

$pdo = (new ConnexionBD())->getPdo();
$stmt = $pdo->query('SELECT * FROM utilisateurs');
$utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Gestion des utilisateurs</h2>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Nom d'utilisateur</th>
            <th>Email</th>
            <th>Rôle</th>
            <th>Admin</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($utilisateurs as $utilisateur): ?>
        <tr>
            <td><?= htmlspecialchars($utilisateur['id']) ?></td>
            <td><?= htmlspecialchars($utilisateur['nom_utilisateur']) ?></td>
            <td><?= htmlspecialchars($utilisateur['email']) ?></td>
            <td><?= htmlspecialchars($utilisateur['role']) ?></td>
            <td><?= $utilisateur['is_admin'] ? 'Oui' : 'Non' ?></td>
            <td>
                <a href="routeur.php?route=modifierUtilisateur&id=<?= $utilisateur['id'] ?>">Modifier</a>
                <a href="routeur.php?route=supprimerUtilisateur&id=<?= $utilisateur['id'] ?>" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')">Supprimer</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<a href="routeur.php?route=ajouterUtilisateur">Ajouter un utilisateur</a>