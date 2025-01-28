<?php
include 'ConfigurationBaseDeDonnees.php';

class DatabaseConnection {
    private $pdo;

    public function __construct() {
        $host = ConfigurationBaseDeDonnees::getNomHote();
        $dbname = ConfigurationBaseDeDonnees::getNomBaseDeDonnees();
        $username = ConfigurationBaseDeDonnees::getLogin();
        $password = ConfigurationBaseDeDonnees::getPassword();
        $port = ConfigurationBaseDeDonnees::getPort();

        try {
            // Création du DSN pour la connexion PDO
            $dsn = "mysql:host=$host;port=$port;dbname=$dbname";
            // Connexion à la base de données avec PDO
            $this->pdo = new PDO($dsn, $username, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Erreur de connexion à la base de données : " . $e->getMessage());
        }
    }

    public function getStats() {
        try {
            $query = "SELECT type_de_box, COUNT(*) AS total FROM locations GROUP BY type_de_box";
            $stmt = $this->pdo->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Erreur lors de la récupération des données : " . $e->getMessage());
        }
    }
}