<?php
use App\Configuration\ConnexionBD;

$connexion = new ConnexionBD();
$pdo = $connexion->getPdo();

$stmt = $pdo->prepare('
    SELECT box_type_id, quantite FROM utilisateur_boxes
    WHERE utilisateur_id = :utilisateur_id
');
$stmt->bindParam(':utilisateur_id', $_SESSION['user']['id']);
$stmt->execute();
$boxesUtilisateur = $stmt->fetchAll(PDO::FETCH_ASSOC);

$prixParM3 = $boxesUtilisateur[0]['prix_par_m3'] ?? null;

$boxes = [];
foreach ($boxesUtilisateur as $box) {
    $boxes[(string)$box['taille']] = $box['nombre_box'];
}

$taillesDisponibles = range(1, 12);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Boxes</title>
</head>
<body class="accueil-page">

<h1>Gestion de vos boxes</h1>

<div class="form-container">
    <div class="form-column">
        <form id="form1">
            <label for="prix_par_m3">Prix par m³ (€) :</label>
            <input type="number" id="prix_par_m3" name="prix_par_m3" step="0.01" value="<?= htmlspecialchars($prixParM3) ?>" required>
            <br><br>

            <?php foreach (array_slice($taillesDisponibles, 0, 6) as $tailleBox): ?>
                <label for="box_<?= str_replace('.', '_', $tailleBox) ?>">Nombre de box <?= $tailleBox ?>m³ :</label>
                <input type="number" name="box_<?= str_replace('.', '_', $tailleBox) ?>" id="box_<?= str_replace('.', '_', $tailleBox) ?>" value="<?= $boxes[(string)$tailleBox] ?? 0 ?>" min="0" required>
                <br>
            <?php endforeach; ?>
        </form>
    </div>

    <div class="form-column">
        <form id="form2" method="POST" action="routeur.php?route=ajouterDonneesAccueil">
            <?php foreach (array_slice($taillesDisponibles, 6) as $tailleBox): ?>
                <label for="box_<?= str_replace('.', '_', $tailleBox) ?>">Nombre de box <?= $tailleBox ?>m³ :</label>
                <input type="number" name="box_<?= str_replace('.', '_', $tailleBox) ?>" id="box_<?= str_replace('.', '_', $tailleBox) ?>" value="<?= $boxes[(string)$tailleBox] ?? 0 ?>" min="0" required>
                <br>
            <?php endforeach; ?>
            <br>
            <button type="submit" onclick="fusionnerEtEnvoyer(event)">Enregistrer</button>
        </form>
    </div>
</div>

<script>
    function fusionnerEtEnvoyer(event) {
        event.preventDefault();

        let form1 = document.getElementById('form1');
        let form2 = document.getElementById('form2');

        let inputsForm1 = form1.querySelectorAll('input');
        inputsForm1.forEach(input => {
            let newInput = document.createElement('input');
            newInput.type = 'hidden';
            newInput.name = input.name;
            newInput.value = input.value;
            form2.appendChild(newInput);
        });

        form2.submit();
    }
</script>

</body>
</html>
