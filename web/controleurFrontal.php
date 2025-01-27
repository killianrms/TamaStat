<?php
require_once __DIR__ . '/../src/Controleur/ControleurGeneral.php';
require_once __DIR__ . '/../src/Lib/Psr4AutoloaderClass.php';

$chargeurDeClasse = new Psr4AutoloaderClass(false); // Activant l'affichage de débogage
$chargeurDeClasse->register();
$chargeurDeClasse->addNamespace('App', __DIR__ . '/..');
$chargeurDeClasse->addNamespace('App\Controleur', __DIR__ . '/../src/Controleur');
$chargeurDeClasse->addNamespace('App\Modele', __DIR__ . '/../src/Modele');
$chargeurDeClasse->addNamespace('App\Configuration', __DIR__ . '/../src/Configuration');
$chargeurDeClasse->addNamespace('App\Modele\DataObject', __DIR__ . '/../src/Modele/DataObject');
$chargeurDeClasse->addNamespace('App\Modele\HTTP', __DIR__ . '/../src/Modele/HTTP');
$chargeurDeClasse->addNamespace('App\Lib', __DIR__ . '/../src/Lib');

$action = $_GET['action'] ?? $_POST['action'] ?? 'afficherAccueil';
$class = $_GET['class'] ?? $_POST['class'] ?? 'App\Controleur\Specifique\ControleurProduit';

if (!class_exists($class)) {
    die("Erreur : La classe '$class' n'existe pas.");
}

$methodesDisponibles = get_class_methods($class);

if (in_array($action, $methodesDisponibles)) {
    call_user_func([$class, $action]);
} else {
    if (method_exists($class, 'afficherErreur')) {
        $class::afficherErreur("Action '$action' non trouvée dans la classe '$class'.");
    } else {
        die("Erreur : La méthode 'afficherErreur' est introuvable dans la classe '$class'.");
    }
}