<?php
namespace App\Modele\HTTP;

use App\Modele\DataObject\Produit;

class Cookie
{
    public static function enregistrer(string $cle, $valeur, ?int $dureeExpiration = null): void
    {
        if ($dureeExpiration === null) {
            $dureeExpiration = time() + 36000;
        }
        setcookie($cle, $valeur, $dureeExpiration);
    }

    public static function lire(string $cle)
    {
        if (!isset($_COOKIE[$cle])) {
            return null;
        }
        return $_COOKIE[$cle];
    }

    public static function supprimer($cle) : void {
        unset($_COOKIE[$cle]);
        setcookie ($cle, "", 1);
    }

    public static function lireArray(string $cle): array {
        $valeur = self::lire($cle);
        if ($valeur === null) {
            return [];
        }
        return explode(',', $valeur);
    }
}