<?php
namespace App\Lib;

use App\Configuration\ConfigurationSite;
use App\Modele\DataObject\Utilisateur;
use App\Modele\Repository\Specifique\UtilisateurRepository;

class VerificationEmail
{
    public static function envoiEmailValidation(Utilisateur $utilisateur): void
    {
        $destinataire = $utilisateur->getEmail();
        $sujet = "Validation de l'adresse email";
        // Pour envoyer un email contenant du HTML
        $enTete = "MIME-Version: 1.0\r\n";
        $enTete .= "Content-type:text/html;charset=UTF-8\r\n";

        // Corps de l'email
        $emailURL = rawurlencode($utilisateur->getEmail());
        $nonceURL = rawurlencode($utilisateur->getNonce());
        $URLAbsolue = ConfigurationSite::getURLAbsolue();
        $lienValidationEmail = "$URLAbsolue?action=validerEmail&controleur=utilisateur&email=$emailURL&nonce=$nonceURL&class=App\Controleur\Specifique\ControleurUtilisateur";
        $corpsEmailHTML = "<a href=\"$lienValidationEmail\">Validation</a>";

        mail($destinataire, $sujet, $corpsEmailHTML, $enTete);
    }

    /**
     * @throws \Exception
     */
    public static function traiterEmailValidation($email, $nonce): bool
    {
        $utilisateur = UtilisateurRepository::recupererUtilisateurParEmail($email);
        if ($utilisateur === null) {
            echo "Utilisateur non trouvé";
            return false;
        }
        if ($utilisateur->getNonce() !== $nonce) {
            echo "Nonce incorrect";
            return false;
        }
        $utilisateur->setEmailValide(true);
        UtilisateurRepository::emailValideTrue($utilisateur);
        return true;
    }
}
