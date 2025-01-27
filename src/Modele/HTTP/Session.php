<?php

namespace App\Modele\HTTP;

use App\Configuration\ConfigurationSite;
use App\Modele\DataObject\Produit;
use Exception;

class Session
{
    private static ?Session $instance = null;

    /**
     * @throws Exception
     */
    private function __construct()
    {
        if (session_start() === false) {
            throw new Exception("La session n'a pas réussi à démarrer.");
        }
    }

    public static function getInstance(): Session
    {
        if (is_null(Session::$instance))
            Session::$instance = new Session();
        return Session::$instance;
    }

    public function contient($nom): bool
    {
        return isset($_SESSION[$nom]);
    }

    public function enregistrer(string $nom, $valeur): void
    {
        $_SESSION[$nom] = $valeur;
    }

    public function lire(string $nom)
    {
        return $_SESSION[$nom] ?? null;
    }

    public function supprimer($nom): void
    {
        unset($_SESSION[$nom]);
    }

    public function detruire() : void
    {
        session_unset();
        session_destroy();
        Cookie::supprimer(session_name());
        Session::$instance = null;
    }

    public function verifierDerniereActivite() {
        $expiration = ConfigurationSite::DUREE_EXPIRATION_SESSION;
        if (isset($_SESSION['derniere_activite']) && (time() - $_SESSION['derniere_activite'] > $expiration)) {
            session_unset();
            session_destroy();
        }
        $_SESSION['derniere_activite'] = time();
    }

    public function ajouterPanier(Produit $p){
        $panier = $this->lirePanier();
        if (isset($panier[$p->getId()])) {
            $panier[$p->getId()] = (int) $panier[$p->getId()] + 1;
        } else {
            $panier[$p->getId()] = 1;
        }

        $cookieValue = '';
        foreach ($panier as $id => $quantite) {
            $cookieValue .= $id . ':' . $quantite . ',';
        }
        $this->enregistrer("panier", rtrim($cookieValue, ','));
    }

    public function lirePanier(): array {
        $valeur = $this->lire("panier");
        if ($valeur === null || $valeur === '' || $valeur === []) {
            return [];
        }
        $panier = [];
        foreach (explode(',', $valeur) as $item) {
            list($id, $quantite) = explode(':', $item);
            $panier[$id] = (int) $quantite;
        }
        return $panier;
    }

    public function retirerPanier(string $id) {
        $panier = $this->lirePanier();
        unset($panier[$id]);
        $cookieValue = '';
        foreach ($panier as $id => $quantite) {
            $cookieValue .= $id . ':' . $quantite . ',';
        }
        $this->enregistrer("panier", rtrim($cookieValue, ','));
    }
}