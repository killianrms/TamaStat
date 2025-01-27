<?php
namespace App\Controleur;

use App\Lib\ConnexionUtilisateur;
use App\Modele\DataObject\Produit;
use App\Modele\HTTP\Cookie;
use App\Modele\HTTP\Session;
use App\Modele\Repository\Specifique\CommandeRepository;
use App\Modele\Repository\Specifique\ProduitRepository;

class ControleurGeneral
{
    protected static function getType(): string
    {
        return "general";
    }
    protected static function getAttributs(): array
    {
        return [];
    }
    protected static function afficherVue(string $cheminVue, array $parametres = []): void
    {
        extract($parametres);
        require_once __DIR__ . "/../vue/$cheminVue";
    }


    // VUES
    public static function afficherAccueil(): void
    {
        $produits = ProduitRepository::recupererTout();
        static::afficherVue('vueGenerale.php', ["titre" => "Accueil", "cheminCorpsVue" => "utilisateur/accueil.php", "produits" => $produits]);
    }
    public static function afficherFormulaireCreation(): void
    {
        $messageErreur = "Requête invalide.";
        static::afficherVue('vueGenerale.php', ["messageErreur" => $messageErreur, "titre" => "Erreur", "cheminCorpsVue" => "utilisateur/erreur.php"]);
    }
    public static function creerDepuisFormulaire(): void
    {
        $messageErreur = "Requête invalide.";
        static::afficherVue('vueGenerale.php', ["messageErreur" => $messageErreur, "titre" => "Erreur", "cheminCorpsVue" => "utilisateur/erreur.php"]);
    }
    public static function afficherErreur(string $messageErreur = "Problème"): void
    {
        static::afficherVue('vueGenerale.php', ["messageErreur" => $messageErreur, "titre" => "Erreur", "cheminCorpsVue" => "utilisateur/erreur.php"]);
    }
    public static function afficherSupprimer(): void {
        $messageErreur = "Requête invalide.";
        static::afficherVue('vueGenerale.php', ["messageErreur" => $messageErreur, "titre" => "Erreur", "cheminCorpsVue" => "utilisateur/erreur.php"]);
    }
    public static function afficherFormulaireMiseAJour(): void
    {
        $messageErreur = "Requête invalide.";
        static::afficherVue('vueGenerale.php', ["messageErreur" => $messageErreur, "titre" => "Erreur", "cheminCorpsVue" => "utilisateur/erreur.php"]);
    }
    public static function miseAJourDepuisFormulaire(): void
    {
        $messageErreur = "Requête invalide.";
        static::afficherVue('vueGenerale.php', ["messageErreur" => $messageErreur, "titre" => "Erreur", "cheminCorpsVue" => "utilisateur/erreur.php"]);
    }

    public static function afficherCreerCompte(): void
    {
        static::afficherVue('vueGenerale.php', ["titre" => "Créer un compte", "cheminCorpsVue" => "utilisateur/formulaireCreationUtilisateur.php"]);
    }
    public static function afficherSeConnecter(): void
    {
        static::afficherVue('vueGenerale.php', ["titre" => "Se connecter", "cheminCorpsVue" => "utilisateur/seConnecter.php"]);
    }
    public static function afficherProfil(): void
    {
        static::afficherVue('vueGenerale.php', ["titre" => "Votre profil", "cheminCorpsVue" => "utilisateur/profil.php"]);
    }
    public static function afficherHistorique(): void
    {
        $historique = CommandeRepository::recupererToutPourUtilisateur(ConnexionUtilisateur::getemailUtilisateurConnecte());
        static::afficherVue('vueGenerale.php', ["titre" => "Votre historique", "cheminCorpsVue" => "utilisateur/historique.php", "historique" => $historique]);
    }

    public static function ajouterAuPanier(): void
    {
        $id = $_GET['id'];
        $produit = ProduitRepository::recupererParId($id);
        Session::getInstance()->ajouterPanier($produit);
        static::afficherAccueil();
    }
    public static function afficherPanier(): void
    {
        $panier = Session::getInstance()->lirePanier();
        if (isset($panier[''])) {
            unset($panier['']);
        }
        $infosPaniers = [];
        foreach ($panier as $id => $quantite) {
            $infosPaniers[] = ["id" => $id, "nom" => self::nomProduit($id), "prix" => self::prixProduit($id), "quantite" => $quantite];
        }
        static::afficherVue('vueGenerale.php', ["titre" => "Panier", "cheminCorpsVue" => "utilisateur/panier.php", "panier" => $infosPaniers]);
    }

    public static function nomProduit(string $id): string {
        $produit = ProduitRepository::recupererParId($id);
        return $produit->getNom();
    }

    public static function prixProduit(string $id): int {
        $produit = ProduitRepository::recupererParId($id);
        return $produit->getPrix();
    }

    public static function payer(): void{
        $idProduit = $_GET['idProduit'];
        $quantite = $_GET['quantite'];
        $utilisateur = ConnexionUtilisateur::getemailUtilisateurConnecte();
        CommandeRepository::ajouterCommande($utilisateur, $idProduit, $quantite);
        Session::getInstance()->retirerPanier($idProduit);
        static::afficherHistorique();
    }

}