<?php
namespace App\Modele\Repository\Specifique;

use App\Modele\DataObject\Produit;
use App\Modele\Repository\ConnexionBaseDeDonnees;
use App\Modele\Repository\AbstractRepository;

class ProduitRepository extends AbstractRepository
{
    protected static function getType(): string {
        return "produit";
    }
    protected static function getAttributs(): array {
        return ["id", "nom", "prix", "nomPhoto"];
    }
    protected static function getClePrimaire(): string {
        return "id";
    }
    public static function recupererParId(string $id): Produit {
        $pdoStatement = ConnexionBaseDeDonnees::getPdo()->prepare("SELECT * FROM Produit WHERE id = :id");
        $pdoStatement->execute(['id' => $id]);
        $ObjetFormatTableau = $pdoStatement->fetch();
        if (!$ObjetFormatTableau) {
            throw new \Exception("Aucun objet trouvé avec l'ID $id dans la table Produit.");
        }
        return ProduitRepository::construireDepuisTableauSQL($ObjetFormatTableau);
    }
    public static function supprimer(string $id): void {
        $pdoStatement = ConnexionBaseDeDonnees::getPdo()->prepare("DELETE FROM Produit WHERE id = :id");
        $pdoStatement->execute(['id' => $id]);
    }
}