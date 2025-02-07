<?php
use App\Configuration\ConnexionBD;

$connexion = new ConnexionBD();
$pdo = $connexion->getPdo();


$utilisateurId = $_SESSION['user']['id'];

try {
    $pdo->beginTransaction();

    $stmtDelete = $pdo->prepare("DELETE FROM utilisateur_boxes WHERE utilisateur_id = :utilisateur_id");
    $stmtDelete->execute([':utilisateur_id' => $utilisateurId]);

    $stmtInsert = $pdo->prepare("
        INSERT INTO utilisateur_boxes (utilisateur_id, box_type_id, quantite) 
        VALUES (:utilisateur_id, :box_type_id, :quantite)
    ");

    foreach ($_POST as $key => $value) {
        if (strpos($key, 'box_') === 0) {
            $boxTypeId = str_replace('box_', '', $key);
            $quantite = intval($value);

            if ($quantite > 0) {
                $stmtInsert->execute([
                    ':utilisateur_id' => $utilisateurId,
                    ':box_type_id' => $boxTypeId,
                    ':quantite' => $quantite
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
