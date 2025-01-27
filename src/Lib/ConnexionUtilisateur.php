<?php

namespace App\Lib;

use App\Modele\HTTP\Session;

class ConnexionUtilisateur
{
// L'utilisateur connecté sera enregistré en session associé à la clé suivante
    private static string $cleConnexion = "_utilisateurConnecte";

    public static function connecter($emailUtilisateur): void
    {
        Session::getInstance()->verifierDerniereActivite();
        Session::getInstance()->enregistrer(self::$cleConnexion, $emailUtilisateur);
    }

    public static function estConnecte(): bool
    {
        return Session::getInstance()->contient(self::$cleConnexion);
    }

    public static function deconnecter(): void
    {
        Session::getInstance()->supprimer(self::$cleConnexion);
    }

    public static function getemailUtilisateurConnecte(): ?string
    {
        return Session::getInstance()->lire(self::$cleConnexion);
    }
}
