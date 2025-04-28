<?php

namespace App\Controleur\Specifique;

use App\Configuration\ConnexionBD;
use PDO;
use Exception; // Added for potential exceptions

class ControleurUtilisateur
{
    private $pdo;
    const MAX_FAILED_ATTEMPTS = 5; // Define max attempts constant

    public function __construct()
    {
        $connexion = new ConnexionBD();
        $this->pdo = $connexion->getPdo();
    }

    public function login($usernameOrEmail, $password)
    {
        // Fetch user data including lock status and failed attempts
        $stmt = $this->pdo->prepare('SELECT id, nom_utilisateur, email, mot_de_passe, is_locked, failed_login_attempts, is_admin FROM utilisateurs WHERE nom_utilisateur = :input OR email = :input');
        $stmt->execute(['input' => $usernameOrEmail]);
        $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($utilisateur) {
            // Check if account is locked
            if ($utilisateur['is_locked'] == 1) {
                $_SESSION['erreur_connexion'] = 'Votre compte est verrouillé. Veuillez contacter l\'administrateur.';
                header('Location: routeur.php?route=connexion&erreur=locked');
                exit;
            }

            // Verify password
            if (password_verify($password, $utilisateur['mot_de_passe'])) {
                // Password is correct, reset failed attempts if necessary
                if ($utilisateur['failed_login_attempts'] > 0) {
                    $updateStmt = $this->pdo->prepare('UPDATE utilisateurs SET failed_login_attempts = 0 WHERE id = :id');
                    $updateStmt->execute(['id' => $utilisateur['id']]);
                }

                // Proceed with successful login
                $_SESSION['user'] = [
                    'id' => $utilisateur['id'],
                    'nom_utilisateur' => $utilisateur['nom_utilisateur'],
                    'email' => $utilisateur['email'],
                    'is_admin' => isset($utilisateur['is_admin']) ? (int)$utilisateur['is_admin'] : 0,
                ];
                // Redirect to accueil or intended page
                $redirectRoute = $_SESSION['redirect_after_login'] ?? 'accueil';
                unset($_SESSION['redirect_after_login']); // Clear the stored redirect route
                header('Location: routeur.php?route=' . $redirectRoute);
                exit;

            } else {
                // Password incorrect, increment failed attempts
                $newFailedAttempts = $utilisateur['failed_login_attempts'] + 1;
                $lockAccount = ($newFailedAttempts >= self::MAX_FAILED_ATTEMPTS);

                $sql = 'UPDATE utilisateurs SET failed_login_attempts = :attempts';
                $params = ['attempts' => $newFailedAttempts, 'id' => $utilisateur['id']];

                if ($lockAccount) {
                    $sql .= ', is_locked = 1'; // Lock the account
                }
                $sql .= ' WHERE id = :id';

                $updateStmt = $this->pdo->prepare($sql);
                $updateStmt->execute($params);

                $_SESSION['erreur_connexion'] = 'Identifiant ou Mot de passe incorrect.';
                if ($lockAccount) {
                     $_SESSION['erreur_connexion'] .= ' Votre compte a été verrouillé après trop de tentatives échouées.';
                     header('Location: routeur.php?route=connexion&erreur=locked');
                } else {
                    header('Location: routeur.php?route=connexion&erreur=credentials');
                }
                exit;
            }
        } else {
            // User not found
            $_SESSION['erreur_connexion'] = 'Identifiant ou Mot de passe incorrect.';
            header('Location: routeur.php?route=connexion&erreur=notfound'); // More specific error if needed
            exit;
        }
    }

    public function getDonneesUtilisateur($userId)
    {
        $stmt = $this->pdo->prepare("SELECT box_type_id, quantite FROM utilisateur_boxes WHERE utilisateur_id = :userId");
        $stmt->execute(['userId' => $userId]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result ?: [];
    }

    public function mettreAJourDonneesUtilisateur($utilisateurId, array $boxes)
    {
        if ($utilisateurId === null) {
            throw new \Exception("Erreur : l'ID utilisateur est manquant.");
        }

        $pdo = $this->pdo; // Use existing PDO connection

        $deleteQuery = "DELETE FROM utilisateur_boxes WHERE utilisateur_id = :utilisateur_id";
        $deleteStmt = $pdo->prepare($deleteQuery);
        $deleteStmt->execute([':utilisateur_id' => $utilisateurId]);

        $insertQuery = "INSERT INTO utilisateur_boxes (utilisateur_id, box_type_id, quantite) VALUES (:utilisateur_id, :box_type_id, :quantite)";
        $insertStmt = $pdo->prepare($insertQuery);

        foreach ($boxes as $boxTypeId => $quantite) {
            if ($quantite >= 0) {
                $insertStmt->execute([
                    ':utilisateur_id' => $utilisateurId,
                    ':box_type_id' => $boxTypeId,
                    ':quantite' => $quantite,
                ]);
            }
        }
    }

    public function changerMotDePasse($utilisateurId, $ancienMdp, $nouveauMdp, $confirmerMdp)
    {
        try {
            if ($nouveauMdp !== $confirmerMdp) {
                throw new Exception("Les nouveaux mots de passe ne correspondent pas.");
            }

            if (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*])[A-Za-z\d!@#$%^&*]{8,}$/', $nouveauMdp)) {
                throw new Exception("Le nouveau mot de passe ne respecte pas les critères de sécurité.");
            }

            // Use the existing PDO connection
            $stmt = $this->pdo->prepare('SELECT mot_de_passe FROM utilisateurs WHERE id = ?');
            $stmt->execute([$utilisateurId]);
            $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$utilisateur || !password_verify($ancienMdp, $utilisateur['mot_de_passe'])) {
                throw new Exception("L'ancien mot de passe est incorrect.");
            }

            $nouveauMdpHash = password_hash($nouveauMdp, PASSWORD_BCRYPT);

            $stmt = $this->pdo->prepare('UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?');
            $stmt->execute([$nouveauMdpHash, $utilisateurId]);

            $_SESSION['succes_message'] = "Mot de passe changé avec succès.";
            header('Location: routeur.php?route=profil');
            exit;

        } catch (Exception $e) {
            $_SESSION['erreur_message'] = $e->getMessage();
            header('Location: routeur.php?route=profil');
            exit;
        }
    }

    // --- Admin Specific Methods ---

    /**
     * Checks if the current user is an administrator. Redirects if not.
     */
    private function checkAdmin()
    {
        if (!isset($_SESSION['user']) || !isset($_SESSION['user']['is_admin']) || $_SESSION['user']['is_admin'] !== 1) {
            // Optionally set an error message
            $_SESSION['erreur_message'] = "Accès non autorisé.";
            // Redirect to login or a generic error page
            header('Location: routeur.php?route=connexion');
            exit;
        }
    }

    /**
     * Displays the user management page for administrators.
     */
    public function adminGestionUtilisateurs()
    {
        $this->checkAdmin(); // Ensure only admins can access

        // The view file itself fetches the user data and handles display
        require_once __DIR__ . '/../../Vue/utilisateur/gestionUtilisateurs.php';
    }

    /**
     * Locks a specific user account.
     * @param int $userId The ID of the user to lock.
     */
    public function adminBloquerUtilisateur($userId)
    {
        $this->checkAdmin(); // Ensure only admins can perform this action

        if (empty($userId) || !filter_var($userId, FILTER_VALIDATE_INT)) {
             $_SESSION['erreur_message'] = "ID utilisateur invalide.";
             header('Location: routeur.php?route=adminGestionUtilisateurs');
             exit;
        }

        // Prevent locking oneself or other admins (optional but recommended)
        if ($userId == $_SESSION['user']['id']) {
            $_SESSION['erreur_message'] = "Vous ne pouvez pas verrouiller votre propre compte.";
            header('Location: routeur.php?route=adminGestionUtilisateurs');
            exit;
        }
        // Add check if the target user is admin if needed

        try {
            $stmt = $this->pdo->prepare('UPDATE utilisateurs SET is_locked = 1 WHERE id = :id AND is_admin = 0'); // Ensure not locking admins
            $stmt->execute(['id' => $userId]);

            if ($stmt->rowCount() > 0) {
                $_SESSION['succes_message'] = "Utilisateur verrouillé avec succès.";
            } else {
                 $_SESSION['erreur_message'] = "Impossible de verrouiller l'utilisateur (peut-être un admin ou ID invalide).";
            }
        } catch (Exception $e) {
            // Log error $e->getMessage();
            $_SESSION['erreur_message'] = "Erreur lors du verrouillage de l'utilisateur.";
        }

        header('Location: routeur.php?route=adminGestionUtilisateurs');
        exit;
    }

    /**
     * Unlocks a specific user account and resets failed login attempts.
     * @param int $userId The ID of the user to unlock.
     */
    public function adminDebloquerUtilisateur($userId)
    {
        $this->checkAdmin(); // Ensure only admins can perform this action

        if (empty($userId) || !filter_var($userId, FILTER_VALIDATE_INT)) {
             $_SESSION['erreur_message'] = "ID utilisateur invalide.";
             header('Location: routeur.php?route=adminGestionUtilisateurs');
             exit;
        }

        try {
            // Reset lock status and failed attempts
            $stmt = $this->pdo->prepare('UPDATE utilisateurs SET is_locked = 0, failed_login_attempts = 0 WHERE id = :id');
            $stmt->execute(['id' => $userId]);

             if ($stmt->rowCount() > 0) {
                $_SESSION['succes_message'] = "Utilisateur déverrouillé avec succès.";
            } else {
                 $_SESSION['erreur_message'] = "Impossible de déverrouiller l'utilisateur (ID invalide?).";
            }
        } catch (Exception $e) {
            // Log error $e->getMessage();
            $_SESSION['erreur_message'] = "Erreur lors du déverrouillage de l'utilisateur.";
        }

        header('Location: routeur.php?route=adminGestionUtilisateurs');
        exit;
    }

} // End of class
