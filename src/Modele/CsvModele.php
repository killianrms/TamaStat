<?php
class DonneesCsvModele {
    private $pdo;

    public function __construct() {
        $this->pdo = new PDO('mysql:host=localhost;dbname=ton_db', 'root', '');
    }

    public function ajouterDonnees($ligne) {
        $stmt = $this->pdo->prepare('INSERT INTO donnees_csv (colonne1, colonne2, colonne3) VALUES (?, ?, ?)');
        $stmt->execute([$ligne[0], $ligne[1], $ligne[2]]);
    }
}
