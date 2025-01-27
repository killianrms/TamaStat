<?php
session_start();

class ControleurUtilisateur {
    public function login($username, $password) {
        // Simule une vérification d'utilisateur
        $utilisateurs = [
            'admin' => 'password123', // Exemple d'utilisateur
        ];

        if (isset($utilisateurs[$username]) && $utilisateurs[$username] === $password) {
            $_SESSION['user'] = $username;
            header('Location: routeur.php?route=accueil');
            exit;
        } else {
            echo '<h2>Identifiants incorrects</h2>';
            require_once __DIR__ . '/../../Vue/utilisateur/formulaireConnexion.php';
        }
    }

    public function deconnexion() {
        session_destroy();
        header('Location: routeur.php?route=connexion');
        exit;
    }
}
