<?php
use App\Configuration\ConnexionBD;

$connexion = new ConnexionBD();
$pdo = $connexion->getPdo();

if (!isset($_SESSION['user']['id'])) {
    die("Erreur : Utilisateur non connecté.");
}

$utilisateurId = $_SESSION['user']['id'];

$prixParM3 = isset($_POST['prix_par_m3']) ? floatval($_POST['prix_par_m3']) : null;

$taillesDisponibles = [1.0, 1.5, 2.0, 2.5, 3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 9.0, 10.0];

try {
    $pdo->beginTransaction();

    $stmtDelete = $pdo->prepare("DELETE FROM boxes_utilisateur WHERE utilisateur_id = :utilisateur_id");
    $stmtDelete->execute([':utilisateur_id' => $utilisateurId]);

    $stmtInsert = $pdo->prepare("INSERT INTO boxes_utilisateur (utilisateur_id, taille, nombre_box, prix_par_m3) VALUES (:utilisateur_id, :taille, :nombre_box, :prix_par_m3)");

    foreach ($taillesDisponibles as $tailleBox) {
        if (isset($_POST["box_$tailleBox"])) {
            $nombreBox = intval($_POST["box_$tailleBox"]);
            if ($nombreBox > 0) {
                $stmtInsert->execute([
                    ':utilisateur_id' => $utilisateurId,
                    ':taille' => $tailleBox,
                    ':nombre_box' => $nombreBox,
                    ':prix_par_m3' => $prixParM3
                ]);
            }
        }
    }

    $pdo->commit();

    header('Location: routeur.php?route=accueil');
    exit();
} catch (Exception $e) {
    $pdo->rollBack();
    die("Erreur lors de l'enregistrement : " . $e->getMessage());
}
