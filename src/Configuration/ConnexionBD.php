<?php
namespace App\Configuration;

use PDO;
use PDOException;

use App\Configuration\ConfigurationBaseDeDonnees;

class ConnexionBD
{
    private $pdo;

    public function __construct()
    {
        try {
            $dsn = 'mysql:host=' . ConfigurationBaseDeDonnees::getNomHote() . ';dbname=' . ConfigurationBaseDeDonnees::getNomBaseDeDonnees() . ';port=' . ConfigurationBaseDeDonnees::getPort();
            $this->pdo = new PDO($dsn, ConfigurationBaseDeDonnees::getLogin(), ConfigurationBaseDeDonnees::getPassword());
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo 'Erreur de connexion : ' . $e->getMessage();
            exit;
        }
    }

    public function getPdo()
    {
        return $this->pdo;
    }
}
?>
