<?php
function verifierSession() {
    session_start();
    if (!isset($_SESSION['utilisateur'])) {
        header('Location: /utilisateur/formulaireConnexion');
        exit;
    }
}
