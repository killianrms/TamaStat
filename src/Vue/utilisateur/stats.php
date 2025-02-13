<?php
USE App\Configuration\ConnexionBD;
USE App\Modele\CsvModele;

$connexion = new ConnexionBD();
$pdo = $connexion->getPdo();
$csvModele = new CsvModele();

$utilisateurId = $_SESSION['user']['id'];

// Récupérer les données pour les statistiques
$boxTypes = $pdo->prepare('SELECT * FROM box_types WHERE utilisateur_id = ?');
$boxTypes->execute([$utilisateurId]);
$boxTypes = $boxTypes->fetchAll(PDO::FETCH_ASSOC);

$locations = $pdo->prepare('SELECT * FROM locations WHERE utilisateur_id = ?');
$locations->execute([$utilisateurId]);
$locations = $locations->fetchAll(PDO::FETCH_ASSOC);

$factures = $pdo->prepare('SELECT * FROM factures WHERE utilisateur_id = ?');
$factures->execute([$utilisateurId]);
$factures = $factures->fetchAll(PDO::FETCH_ASSOC);

$utilisateurBoxes = $pdo->prepare('SELECT * FROM utilisateur_boxes WHERE utilisateur_id = ?');
$utilisateurBoxes->execute([$utilisateurId]);
$utilisateurBoxes = $utilisateurBoxes->fetchAll(PDO::FETCH_ASSOC);

// Associer les quantites aux boxTypes
$boxTypesById = [];
$boxQuantites = [];
foreach ($boxTypes as $boxType) {
    $boxTypesById[$boxType['id']] = $boxType;
}
foreach ($utilisateurBoxes as $box) {
    $boxQuantites[$box['box_type_id']] = $box['quantite'];
}

// Calculer les revenus HT, TVA et TTC
$revenuTotalHT = array_sum(array_column($factures, 'total_ht'));
$revenuTotalTVA = array_sum(array_column($factures, 'tva'));
$revenuTotalTTC = array_sum(array_column($factures, 'total_ttc'));

// Calculer le nombre total de box disponibles
$totalBoxDisponibles = array_sum($boxQuantites);

// Calculer le nombre de box loués
$totalBoxLoues = count($locations);

// Calculer le taux d'occupation
$tauxOccupationGlobal = ($totalBoxDisponibles > 0) ? round(($totalBoxLoues / $totalBoxDisponibles) * 100, 2) : 0;

// Calculer le revenu mensuel
$revenuMensuel = [];
$nouveauxContratsParMois = [];
foreach ($locations as $location) {
    $mois = date('Y-m', strtotime($location['date_debut']));
    $boxTypeId = $location['box_type_id'];
    $prixTTC = isset($boxTypesById[$boxTypeId]) ? $boxTypesById[$boxTypeId]['prix_ttc'] : 0;

    $revenuMensuel[$mois] = ($revenuMensuel[$mois] ?? 0) + $prixTTC;
    $nouveauxContratsParMois[$mois] = ($nouveauxContratsParMois[$mois] ?? 0) + 1;
}

// Préparer les données pour les graphiques
$boxLabels = array_column($boxTypes, 'denomination');
$revenuParBox = [];
$occupationParBox = [];
$maxBoxParType = [];

foreach ($boxTypes as $boxType) {
    $boxTypeId = $boxType['id'];
    $locationsBox = array_filter($locations, fn($loc) => $loc['box_type_id'] == $boxTypeId);

    $revenuParBox[$boxTypeId] = count($locationsBox) * $boxType['prix_ttc'];
    $occupationParBox[$boxTypeId] = count($locationsBox);
    $maxBoxParType[$boxTypeId] = $boxQuantites[$boxTypeId] ?? 0;
}
?>
