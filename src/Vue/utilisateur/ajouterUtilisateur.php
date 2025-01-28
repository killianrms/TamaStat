<?php
session_start();

if ($_SESSION['role'] !== 'admin') {
    echo "Accès non autorisé!";
    exit;
}


use App\Configuration\ConnexionBD;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_utilisateur = htmlspecialchars($_POST['nom_utilisateur']);
    $mot_de_passe = $_POST['mot_de_passe'];
    $email = $_POST['email'];
    $role = $_POST['role'];

    $pdo = (new ConnexionBD())->getPdo();
    $stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE nom_utilisateur = :nom_utilisateur OR email = :email');
    $stmt->execute(['nom_utilisateur' => $nom_utilisateur, 'email' => $email]);
    $utilisateurExist = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($utilisateurExist) {
        echo "Cet utilisateur existe déjà.";
    } else {
        $mot_de_passe_hache = password_hash($mot_de_passe, PASSWORD_BCRYPT);

        $stmt = $pdo->prepare('INSERT INTO utilisateurs (nom_utilisateur, mot_de_passe, email, role) VALUES (:nom_utilisateur, :mot_de_passe, :email, :role)');
        $stmt->execute([
            ':nom_utilisateur' => $nom_utilisateur,
            ':mot_de_passe' => $mot_de_passe_hache,
            ':email' => $email,
            ':role' => $role
        ]);

        echo "Utilisateur ajouté avec succès!";
    }
}
?>
