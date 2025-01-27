<?php

namespace App\Modele\Repository\Specifique;

use App\Modele\DataObject\Utilisateur;
use App\Modele\Repository\ConnexionBaseDeDonnees;
use App\Modele\Repository\AbstractRepository;

class UtilisateurRepository extends AbstractRepository{

    protected static function getType(): string {
        return "utilisateur";
    }
    protected static function getAttributs(): array {
        return ["email", "prenom", "nom", "adresse", "ville", "mdpHache", "estAdmin", "emailValide", "nonce"];
    }

    protected static function getClePrimaire(): string {
        return "email";
    }

    /**
     * @throws \Exception
     */
    public static function recupererUtilisateurParEmail(string $email): ?Utilisateur {
        $pdoStatement = ConnexionBaseDeDonnees::getPdo()->prepare("SELECT * from Utilisateur WHERE email = :emailTag");
        $pdoStatement->execute(["emailTag" => $email]);
        $utilisateurFormatTableau = $pdoStatement->fetch();
        if (!$utilisateurFormatTableau) {
            return null;
        }
        return UtilisateurRepository::construireDepuisTableauSQL($utilisateurFormatTableau);
    }
    public static function supprimer(String $email): void {
        $pdoStatement = ConnexionBaseDeDonnees::getPdo()->prepare("DELETE FROM Utilisateur WHERE `Utilisateur`.`email` = :emailTag");
        $pdoStatement->execute(["emailTag" => $email]);
    }

    public static function emailValideTrue($objet): void
    {
        $email = $objet->getEmail();
        $sql = "UPDATE Utilisateur SET emailValide = 1 WHERE `Utilisateur`.`email` = :emailTag";
        $pdoStatement = ConnexionBaseDeDonnees::getPdo()->prepare($sql);
        $pdoStatement->execute(["emailTag" => $email]);
    }
}