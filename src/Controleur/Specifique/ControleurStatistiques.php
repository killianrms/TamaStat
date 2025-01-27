<?php
class ControleurStatistiques
{
    public function importerCsv()
    {
        if ($_FILES['fichier']['error'] === UPLOAD_ERR_OK) {
            $cheminFichier = $_FILES['fichier']['tmp_name'];
            $handle = fopen($cheminFichier, 'r');
            $modele = new DonneesCsvModele();

            while (($ligne = fgetcsv($handle, 1000, ',')) !== false) {
                $modele->ajouterDonnees($ligne);
            }
            fclose($handle);

            header('Location: /utilisateur/accueil?import=success');
            exit;
        } else {
            header('Location: /utilisateur/accueil?import=error');
            exit;
        }
    }
}