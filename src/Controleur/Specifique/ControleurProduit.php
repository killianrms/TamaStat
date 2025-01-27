<?php
namespace App\Controleur\Specifique;
use App\Controleur\ControleurGeneral;
use App\Modele\DataObject\Produit;
use App\Modele\Repository\Specifique\ProduitRepository;

class ControleurProduit extends ControleurGeneral
{
    protected static function getType(): string
    {
        return "produit";
    }
    protected static function getAttributs(): array
    {
        return ["id", "nom", "prix", "nomPhoto"];
    }

    // FORMULAIRES
    /**
     * @throws \Exception
     */
    public static function creerDepuisFormulaire(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nom = $_POST['nom'];
            $prix = $_POST['prix'];

            $dossierCible = __DIR__ . "/../../vue/utilisateur/assets/images/produit/";
            $nomPhoto = self::gererUploadImage('image', $dossierCible);

            $produit = new Produit("NULL", $nom, $prix, $nomPhoto);
            ProduitRepository::ajouter($produit);
            self::afficherAccueil();
        } else {
            self::afficherErreur("Requête invalide.");
        }
    }
    /**
     * @throws \Exception
     */
    public static function miseAJourDepuisFormulaire(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'];
            $nom = $_POST['nom'];
            $prix = $_POST['prix'];

            if (!is_numeric($prix)) {
                self::afficherErreur("Le prix doit être un nombre.");
                return;
            }

            $dossierCible = __DIR__ . "/../../vue/utilisateur/assets/images/produit/";
            $nomPhoto = self::gererUploadImage('image', $dossierCible);

            if (is_null($nomPhoto)) {
                $produitActuel = ProduitRepository::recupererParId($id);
                $nomPhoto = $produitActuel->getNomPhoto();
            }

            $produit = new Produit($id, $nom, $prix, $nomPhoto);
            ProduitRepository::modifier($produit);
            self::afficherAccueil();
        } else {
            self::afficherErreur("Requête invalide.");
        }
    }
    public static function gererUploadImage(string $champFichier, string $dossierCible): ?string
    {
        if (isset($_FILES[$champFichier]) && $_FILES[$champFichier]['error'] === UPLOAD_ERR_OK) {
            $nomTemporaire = $_FILES[$champFichier]['tmp_name'];
            $nomOriginal = basename($_FILES[$champFichier]['name']);
            $cheminCible = rtrim($dossierCible, '/') . '/' . $nomOriginal;

            if (move_uploaded_file($nomTemporaire, $cheminCible)) {
                return $nomOriginal;
            }
        }

        return null;
    }

    // VUES
    public static function afficherSupprimer(): void {
        $id = $_GET['id'];
        ProduitRepository::supprimer($id);
        static::afficherAccueil();
    }
    public static function afficherFormulaireMiseAJour(): void
    {
        $id = $_GET['id'];
        $objet = ProduitRepository::recupererParId($id);
        static::afficherVue('vueGenerale.php', ["titre" => "Mise à jour", "cheminCorpsVue" => "utilisateur/formulaireMiseAJourProduit.php", "produit" => $objet]);
    }
    public static function afficherFormulaireCreation(): void
    {
        static::afficherVue('vueGenerale.php', ["titre" => "Création", "cheminCorpsVue" => "utilisateur/formulaireCreationProduit.php", "type" => static::getType()]);
    }
}
