<?php
namespace App\Controleur\Specifique;

use App\Controleur\ControleurGeneral;
use App\Lib\ConnexionUtilisateur;
use App\Lib\VerificationEmail;
use App\Modele\DataObject\Utilisateur as Utilisateur;
use App\Modele\Repository\Specifique\UtilisateurRepository as UtilisateurRepository;
use App\Lib\MotDePasse as MotDePasse;

class ControleurUtilisateur extends ControleurGeneral
{
    protected static function getType(): string
    {
        return "utilisateur";
    }

    protected static function getAttributs(): array
    {
        return ["email", "prenom", "nom", "adresse", "ville", "mdpHache", "estAdmin", "emailValide", "nonce"];
    }

    // FORMULAIRES

    /**
     * @throws \Exception
     */
    public static function creerDepuisFormulaire(): void
    {
        $email = $_POST['email'];
        $prenom = $_POST['prenom'];
        $nom = $_POST['nom'];
        $adresse = $_POST['adresse'];
        $ville = $_POST['ville'];
        $mdp = $_POST['mdp'];
        $mdp2 = $_POST['mdpConfirm'];

        if ($mdp !== $mdp2) {
            self::afficherErreur("Les mots de passe ne correspondent pas.");
            return;
        }
        if (UtilisateurRepository::recupererUtilisateurParEmail($email) != null) {
            self::afficherErreur("Un compte avec cet email existe déjà.");
            return;
        }

        $mdpHache = MotDePasse::hacher($mdp);
        $nouvelUtilisateur = new Utilisateur($email, $prenom, $nom, $adresse, $ville, $mdpHache, false, false, MotDePasse::genererChaineAleatoire());

        try {
            UtilisateurRepository::ajouter($nouvelUtilisateur);
            VerificationEmail::envoiEmailValidation($nouvelUtilisateur);
            self::afficherVerifierEmail();
        } catch (\Exception $e) {
            self::afficherErreur("Erreur lors de la création du compte : " . $e->getMessage());
        }
    }

    /**
     * @throws \Exception
     */
    public static function miseAJourDepuisFormulaire(): void
    {
        $email = ConnexionUtilisateur::getemailUtilisateurConnecte();
        $utilisateur = UtilisateurRepository::recupererUtilisateurParEmail($email);

        $newEmail = $_POST['email'];
        $prenom = $_POST['prenom'];
        $nom = $_POST['nom'];
        $adresse = $_POST['adresse'];
        $ville = $_POST['ville'];

        $mdp = $_POST['mdp'];
        $newMdp = $_POST['newMdp'];
        $newMdp2 = $_POST['newMdpConfirm'];
        $estAdmin = $utilisateur->getEstAdminBool();

        if ($newEmail !== $email && UtilisateurRepository::recupererUtilisateurParEmail($newEmail) != null) {
            self::afficherErreur("Un compte avec cet email existe déjà.");
            return;
        }

        $emailValide = $utilisateur->getEmailValideBool();
        $nonce = $utilisateur->getNonce();

        if (!MotDePasse::verifier($mdp, $utilisateur->getMdpHache())) {
            self::afficherErreur("Mot de passe actuel incorrect.");
            return;
        }
        if ($newMdp !== "" && $newMdp2 !== "" && $newMdp !== $newMdp2) {
            self::afficherErreur("Les nouveaux mots de passe ne correspondent pas.");
            return;
        }

        if ($newMdp !== "") {
            $mdpHache = MotDePasse::hacher($newMdp);
        } else {
            $mdpHache = $utilisateur->getMdpHache();
        }

        $newUtilisateur = new Utilisateur($newEmail, $prenom, $nom, $adresse, $ville, $mdpHache, $estAdmin, $emailValide, $nonce);

        try {
            UtilisateurRepository::modifier($newUtilisateur);
            if ($newEmail !== $email) {
                VerificationEmail::envoiEmailValidation($newUtilisateur);
                self::afficherVerifierEmail();
            }
            self::afficherAccueil();
        } catch (\Exception $e) {
            self::afficherErreur("Erreur lors de la mise à jour : " . $e->getMessage());
        }
    }

    public static function connecter(): void
    {
        $email = $_POST['email'];
        $mdp = $_POST['mdp'];
        $utilisateur = UtilisateurRepository::recupererUtilisateurParEmail($email);
        if ($utilisateur == null) {
            self::afficherErreur("Email ou mot de passe incorrect.");
            return;
        }
        if (!MotDePasse::verifier($mdp, $utilisateur->getmdpHache())) {
            self::afficherErreur("Email ou mot de passe incorrect.");
            return;
        }
        if (!$utilisateur->getEmailValideBool()) {
            self::afficherErreur("Veuillez valider votre email.");
            return;
        }
        ConnexionUtilisateur::connecter($utilisateur->getEmail());
        self::afficherAccueil();
    }

    // VUES
    public static function afficherSupprimer(): void
    {
        $email = ConnexionUtilisateur::getemailUtilisateurConnecte();
        UtilisateurRepository::supprimer($email);
        ConnexionUtilisateur::deconnecter();
        self::afficherAccueil();
    }

    public static function afficherFormulaireMiseAJour(): void
    {
        $email = ConnexionUtilisateur::getemailUtilisateurConnecte();
        $utilisateur = UtilisateurRepository::recupererUtilisateurParEmail($email);
        self::afficherVue('vueGenerale.php', ["titre" => "Mise à jour de votre profil", "cheminCorpsVue" => "utilisateur/formulaireMiseAJourUtilisateur.php", "utilisateur" => $utilisateur]);
    }

    public static function afficherCreerCompte(): void
    {
        static::afficherVue('vueGenerale.php', ["titre" => "Créer un compte", "cheminCorpsVue" => "utilisateur/formulaireCreationUtilisateur.php"]);
    }

    public static function afficherSeConnecter(): void
    {
        static::afficherVue('vueGenerale.php', ["titre" => "Se connecter", "cheminCorpsVue" => "utilisateur/formulaireConnexion.php"]);
    }

    public static function afficherProfil(): void
    {
        static::afficherVue('vueGenerale.php', ["titre" => "Votre profil", "cheminCorpsVue" => "utilisateur/profil.php"]);
    }

    public static function deconnecter(): void
    {
        ConnexionUtilisateur::deconnecter();
        self::afficherAccueil();
    }

    // Mail
    public static function afficherVerifierEmail(): void
    {
        static::afficherVue('vueGenerale.php', ["messageErreur" => "Pour confirmer votre compte, veuillez verifier votre email", "titre" => "Verification", "cheminCorpsVue" => "utilisateur/verifierEmail.php"]);
    }

    /**
     * @throws \Exception
     */
    public static function validerEmail(): void
    {
        $email = $_GET['email'];
        $nonce = $_GET['nonce'];
        if (VerificationEmail::traiterEmailValidation($email, $nonce)) {
            self::afficherAccueil();
        } else {
            self::afficherErreur("Erreur lors de la validation de l'email.");
        }
    }
}
