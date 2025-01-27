<?php

namespace App\Modele\Repository\Specifique;

use App\Modele\DataObject\Commande;
use App\Modele\Repository\AbstractRepository;
use App\Modele\Repository\ConnexionBaseDeDonnees;

class CommandeRepository extends AbstractRepository
{
    public static function ajouterCommande(string $utilisateur, $idProduit, $quantite)
    {
        $pdoStatement = ConnexionBaseDeDonnees::getPdo()->prepare("INSERT INTO Historique (idProduit, idUtilisateur, date, quantite) VALUES (:idProduit, :idUtilisateur, :date, :quantite)");
        $pdoStatement->execute([
            "idProduit" => $idProduit,
            "idUtilisateur" => $utilisateur,
            "date" => date("Y-m-d"),
            "quantite" => $quantite
        ]);
    }

    protected static function getType(): string {
        return "commande";
    }

    protected static function getAttributs(): array
    {
        return ["idUtilisateur", "idProduit", "quantite", "date"];
    }

    protected static function getClePrimaire(): string
    {
        return "id";
    }

    public static function recupererParClePrimaire(string $id): Commande {
        $pdoStatement = ConnexionBaseDeDonnees::getPdo()->prepare("SELECT * FROM Historique WHERE idHistorique = :id");
        $pdoStatement->execute(['id' => $id]);
        $ObjetFormatTableau = $pdoStatement->fetch();
        if (!$ObjetFormatTableau) {
            throw new \Exception("Aucun objet rencontré avec l'ID $id dans la table Commande.");
        }
        return CommandeRepository::construireDepuisTableauSQL($ObjetFormatTableau);
    }

    public static function recupererToutPourUtilisateur(string $idUtilisateur): array {
        $pdoStatement = ConnexionBaseDeDonnees::getPdo()->prepare("SELECT * FROM Historique WHERE idUtilisateur = :idUtilisateur");
        $pdoStatement->execute(['idUtilisateur' => $idUtilisateur]);
        $ObjetsFormatTableau = $pdoStatement->fetchAll();
        if (!$ObjetsFormatTableau) {
            throw new \Exception("Aucun objet rencontré avec l'ID $idUtilisateur dans la table Commande.");
        }

        $tableau = [];
        foreach ($ObjetsFormatTableau as $ObjetFormatTableau) {
            $tableau[] = CommandeRepository::construireDepuisTableauSQL($ObjetFormatTableau);
        }
        return $tableau;
    }
}