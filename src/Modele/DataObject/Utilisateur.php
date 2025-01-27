<?php
namespace App\Modele\DataObject;

class Utilisateur {
    private string $email;
    private string $prenom;
    private string $nom;
    private string $adresse;
    private string $ville;
    private string $mdpHache;
    private bool $estAdmin;
    private bool $emailValide;
    private string $nonce;

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPrenom(): string
    {
        return $this->prenom;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function getAdresse(): string
    {
        return $this->adresse;
    }

    public function getVille(): string
    {
        return $this->ville;
    }

    public function getMdpHache(): string
    {
        return $this->mdpHache;
    }

    public function setEstAdmin(bool $estAdmin): void
    {
        $this->estAdmin = $estAdmin;
    }

    public function getEstAdmin(): String
    {
        if ($this->estAdmin) {
            return "1";
        } else {
            return "0";
        }
    }

    public function getEmailValide(): String
    {
        if ($this->estAdmin) {
            return "1";
        } else {
            return "0";
        }
    }

    public function getEstAdminBool(): bool
    {
        return $this->estAdmin;
    }

    public function getEmailValideBool(): bool
    {
        return $this->emailValide;
    }

    public function getNonce(): string
    {
        return $this->nonce;
    }

    public function setEmailValide(bool $emailValide): void
    {
        $this->emailValide = $emailValide;
    }

    public function __construct(string $email, string $prenom, string $nom, string $adresse, string $ville, string $mdpHache, bool $estAdmin = false, bool $emailValide = false, string $nonce = "") {
        $this->email = $email;
        $this->prenom = $prenom;
        $this->nom = $nom;
        $this->adresse = $adresse;
        $this->ville = $ville;
        $this->mdpHache = $mdpHache;
        $this->estAdmin = $estAdmin;
        $this->emailValide = $emailValide;
        $this->nonce = $nonce;
    }

    public function __toString() {
        return $this->prenom . ' ' . $this->nom . ' (' . $this->email . ')';
    }
}