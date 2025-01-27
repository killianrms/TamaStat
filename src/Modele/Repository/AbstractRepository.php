<?php
namespace App\Modele\Repository;
class AbstractRepository
{
    protected static function getType(): string {
        return "general";
    }
    protected static function getAttributs(): array {
        return [];
    }
    public static function recupererTout(): array {
        $Objets = [];
        $type = static::getType();
        $pdoStatement = ConnexionBaseDeDonnees::getPdo()->query("SELECT * FROM ". ucfirst($type));

        $repository = ucfirst($type) . "Repository";
        $namespace = "App\\Modele\\Repository\\Specifique\\" . $repository;
        foreach ($pdoStatement as $ObjetFormatTableau) {
            $Objets[] = $namespace::construireDepuisTableauSQL($ObjetFormatTableau);
        }
        return $Objets;
    }
    public static function ajouter($objet): void
    {
        $type = ucfirst(static::getType());
        $attributs = static::getAttributs();

        $attributsSansId = array_filter($attributs, fn($attribut) => $attribut !== 'id');

        $sql = "INSERT INTO " . $type . " (" . implode(", ", $attributsSansId) . ") 
            VALUES (" . implode(", ", array_map(fn($attribut) => ":$attribut", $attributsSansId)) . ")";

        $pdoStatement = ConnexionBaseDeDonnees::getPdo()->prepare($sql);

        $values = [];
        foreach ($attributsSansId as $attribut) {
            $getter = "get" . ucfirst($attribut);

            if (method_exists($objet, $getter)) {
                $values[$attribut] = $objet->$getter();
            } else {
                throw new \Exception("Méthode $getter inexistante dans l'objet de type " . get_class($objet));
            }
        }

        try {
            $pdoStatement->execute($values);
        } catch (\PDOException $e) {
            throw new \Exception("Erreur lors de l'insertion : " . $e->getMessage());
        }
    }
    public static function modifier($objet): void
    {
        $type = static::getType();
        $attributs = static::getAttributs();

        $clePrimaire = static::getClePrimaire();

        $setClause = implode(", ", array_map(fn($attribut) => "$attribut = :$attribut", $attributs));

        $sql = "UPDATE " . ucfirst($type) . " SET $setClause WHERE $clePrimaire = :clePrimaire";

        $valeurs = [];
        foreach ($attributs as $attribut) {
            $getter = "get" . ucfirst($attribut);
            if (method_exists($objet, $getter)) {
                $valeurs[$attribut] = $objet->$getter();
            } else {
                throw new \Exception("Méthode $getter inexistante dans l'objet de type " . get_class($objet));
            }
        }

        $getterCle = "get" . ucfirst($clePrimaire);
        if (!method_exists($objet, $getterCle)) {
            throw new \Exception("Méthode $getterCle inexistante pour la clé primaire.");
        }
        $valeurs['clePrimaire'] = $objet->$getterCle();

        $pdoStatement = ConnexionBaseDeDonnees::getPdo()->prepare($sql);
        $pdoStatement->execute($valeurs);
    }

    public static function construireDepuisTableauSQL(array $ObjetFormatTableau): object {
        $type = static::getType();
        $namespace = "App\\Modele\\DataObject\\" . ucfirst($type);

        $attributs = static::getAttributs();
        $valeurs = [];
        foreach ($attributs as $attribut) {
            if (array_key_exists($attribut, $ObjetFormatTableau)) {
                $valeurs[] = $ObjetFormatTableau[$attribut];
            } else {
                throw new \Exception("Attribut '$attribut' manquant dans le tableau SQL.");
            }
        }

        return new $namespace(...$valeurs);
    }
}