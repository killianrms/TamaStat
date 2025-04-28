<?php

namespace App\Controleur\Specifique;

use App\Configuration\ConnexionBD;
use PDO;

class ControleurUtilisateur
{
    private $pdo;

    public function __construct()
    {
        $connexion = new ConnexionBD();
        $this->pdo = $connexion->getPdo();
    }

    public function login($usernameOrEmail, $password)
    {
        // Fetch user including lockout status and failed attempts
        $stmt = $this->pdo->prepare('SELECT id, nom_utilisateur, email, mot_de_passe, is_locked, failed_login_attempts, is_admin FROM utilisateurs WHERE nom_utilisateur = :input OR email = :input');
        $stmt->execute(['input' => $usernameOrEmail]);
        $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($utilisateur) {
            // Check if account is locked
            if ($utilisateur['is_locked'] == 1) {
                $_SESSION['erreur_connexion'] = 'Votre compte est verrouillé.';
                header('Location: routeur.php?route=connexion&erreur=locked');
                exit;
            }

            // Verify password
            if (password_verify($password, $utilisateur['mot_de_passe'])) {
                // Correct password: Reset failed attempts and log in
                $resetStmt = $this->pdo->prepare('UPDATE utilisateurs SET failed_login_attempts = 0 WHERE id = :id');
                $resetStmt->execute(['id' => $utilisateur['id']]);

                $_SESSION['user'] = [
                    'id' => $utilisateur['id'],
                    'nom_utilisateur' => $utilisateur['nom_utilisateur'],
                    'email' => $utilisateur['email'],
                    'is_admin' => isset($utilisateur['is_admin']) ? (int)$utilisateur['is_admin'] : 0,
                ];
                header('Location: routeur.php?route=accueil');
                exit;
            } else {
                // Incorrect password: Increment failed attempts
                $newFailedAttempts = $utilisateur['failed_login_attempts'] + 1;
                $updateStmt = $this->pdo->prepare('UPDATE utilisateurs SET failed_login_attempts = :attempts WHERE id = :id');
                $updateStmt->execute(['attempts' => $newFailedAttempts, 'id' => $utilisateur['id']]);

                // Lock account if attempts reach threshold (5)
                if ($newFailedAttempts >= 5) {
                    $lockStmt = $this->pdo->prepare('UPDATE utilisateurs SET is_locked = 1 WHERE id = :id');
                    $lockStmt->execute(['id' => $utilisateur['id']]);
                    // Optionally add a specific locked message, but generic is fine too
                    // $_SESSION['erreur_connexion'] = 'Trop de tentatives échouées. Votre compte a été verrouillé.';
                }

                $_SESSION['erreur_connexion'] = 'Identifiant ou Mot de passe incorrect';
                header('Location: routeur.php?route=connexion&erreur=1');
                exit;
            }
        } else {
            // User not found
            $_SESSION['erreur_connexion'] = 'Identifiant ou Mot de passe incorrect';
            header('Location: routeur.php?route=connexion&erreur=1');
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

        $pdo = $this->pdo;

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

    public function changerMotDePasse($utilisateurId, $ancienMdp, $nouveauMdp, $confirmerMdp) {
        try {
            if ($nouveauMdp !== $confirmerMdp) {
                throw new Exception("Les nouveaux mots de passe ne correspondent pas.");
            }

            if (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*])[A-Za-z\d!@#$%^&*]{8,}$/', $nouveauMdp)) {
                throw new Exception("Le nouveau mot de passe ne respecte pas les critères de sécurité.");
            }

            $pdo = (new \App\Configuration\ConnexionBD())->getPdo();

            $stmt = $pdo->prepare('SELECT mot_de_passe FROM utilisateurs WHERE id = ?');
            $stmt->execute([$utilisateurId]);
            $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$utilisateur || !password_verify($ancienMdp, $utilisateur['mot_de_passe'])) {
                throw new Exception("L'ancien mot de passe est incorrect.");
            }

            $nouveauMdpHash = password_hash($nouveauMdp, PASSWORD_BCRYPT);

            $stmt = $pdo->prepare('UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?');
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

public function toggleLockStatus($targetUserId)
    {
        // Double-check admin status (already checked in router, but good practice)
        if (!isset($_SESSION['user']) || $_SESSION['user']['is_admin'] !== 1) {
            $_SESSION['erreur_message'] = "Accès non autorisé.";
            header('Location: routeur.php?route=accueil'); // Redirect non-admins away
            exit;
        }

        // Prevent admin from locking/unlocking themselves
        if ($targetUserId == $_SESSION['user']['id']) {
            $_SESSION['erreur_message'] = "Vous ne pouvez pas modifier votre propre statut de verrouillage.";
            header('Location: routeur.php?route=gestionUtilisateurs');
            exit;
        }

        try {
            // Fetch current status
            $stmt = $this->pdo->prepare('SELECT is_locked FROM utilisateurs WHERE id = :id');
            $stmt->execute(['id' => $targetUserId]);
            $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$utilisateur) {
                throw new \Exception("Utilisateur non trouvé.");
            }

            // Determine new status and if failed attempts need reset
            $newLockedStatus = $utilisateur['is_locked'] ? 0 : 1;
            $resetFailedAttempts = ($newLockedStatus === 0); // Reset if unlocking

            // Update database
            if ($resetFailedAttempts) {
                $updateStmt = $this->pdo->prepare('UPDATE utilisateurs SET is_locked = :locked, failed_login_attempts = 0 WHERE id = :id');
            } else {
                $updateStmt = $this->pdo->prepare('UPDATE utilisateurs SET is_locked = :locked WHERE id = :id');
            }
            $updateStmt->execute(['locked' => $newLockedStatus, 'id' => $targetUserId]);

            $_SESSION['succes_message'] = "Statut de l'utilisateur mis à jour avec succès.";

        } catch (\Exception $e) {
            $_SESSION['erreur_message'] = "Erreur lors de la mise à jour du statut : " . $e->getMessage();
        }

        // Redirect back to user management page
        header('Location: routeur.php?route=gestionUtilisateurs');
        exit;
    }
}
