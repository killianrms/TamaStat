<?php

namespace App\Modele\DataObject;

class Commande
{
    private string $idUtilisateur;
    private string $idProduit;
    private string $quantite;
    private string $date;

    public function __construct(string $idUtilisateur, string $idProduit, string $quantite, string $date) {
        $this->idUtilisateur = $idUtilisateur;
        $this->idProduit = $idProduit;
        $this->quantite = $quantite;
        $this->date = $date;
    }


    public function getIdUtilisateur(): string {
        return $this->idUtilisateur;
    }

    public function getIdProduit(): string {
        return $this->idProduit;
    }

    public function getQuantite(): string {
        return $this->quantite;
    }

    public function getDate(): string {
        return $this->date;
    }
}