<?php
namespace App\Modele\DataObject;
class Produit
{
    private string $id;
    private string $nom;
    private string $prix;
    private string $nomPhoto;

    /**
     * @param string $id
     * @param string $nom
     * @param string $prix
     * @param string $photo
     */
    public function __construct(string $id, string $nom, string $prix, string $photo)
    {
        $this->id = $id;
        $this->nom = $nom;
        $this->prix = $prix;
        $this->nomPhoto = $photo;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): void
    {
        $this->nom = $nom;
    }

    public function getPrix(): string
    {
        return $this->prix;
    }

    public function setPrix(string $prix): void
    {
        $this->prix = $prix;
    }

    public function getNomPhoto(): string
    {
        return $this->nomPhoto;
    }

    public function setNomPhoto(string $nomPhoto): void
    {
        $this->nomPhoto = $nomPhoto;
    }

    public function __toString(): string {
        $imageUrl = '../src/vue/utilisateur/assets/images/produit/' . htmlspecialchars($this->getNomPhoto());
        $text =
        '<div class="product-card">' .
        '<img src="' . $imageUrl . '" alt="' . htmlspecialchars($this->getNom()) . '" class="product-image">' .
        '<h3>' . htmlspecialchars($this->getNom()) . '</h3>' .
        '<p>' . htmlspecialchars($this->getPrix()) . '€</p>' .
        '<div class="product-buttons">';
        return $text;
    }
}