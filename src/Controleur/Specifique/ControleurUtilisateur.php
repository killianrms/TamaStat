<?php
class ControleurUtilisateur {
    public function connexion() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nom_utilisateur = $_POST['nom_utilisateur'];
            $mot_de_passe = $_POST['mot_de_passe'];

            $modele = new ConnexionUtilisateur();
            $utilisateur = $modele->verifierUtilisateur($nom_utilisateur);

            if ($utilisateur && password_verify($mot_de_passe, $utilisateur['mot_de_passe'])) {
                session_start();
                $_SESSION['utilisateur'] = $utilisateur['id'];
                header('Location: /utilisateur/accueil');
                exit;
            } else {
                header('Location: /utilisateur/erreur');
                exit;
            }
        }
    }
}
