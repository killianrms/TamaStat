<?php
namespace App\Configuration;

class ConfigurationBaseDeDonnees
{

    static private array $configurationBaseDeDonnees = array(
        // Le nom d'hote est webinfo a l'IUT
        // ou localhost sur votre machine
        //
        // ou webinfo.iutmontp.univ-montp2.fr
        // pour accéder à webinfo depuis l'extérieur
        'nomHote' => getenv('DB_HOST') ?: 'localhost', // Fallback to 'localhost' if DB_HOST is not set
        // A l'IUT, vous avez une base de données nommee comme votre login
        // Sur votre machine, vous devrez creer une base de données
        'nomBaseDeDonnees' => getenv('DB_NAME') ?: 'default_db', // Fallback if DB_NAME is not set
        // À l'IUT, le port de MySQL est particulier : 3316
        // Ailleurs, on utilise le port par défaut : 3306
        'port' => getenv('DB_PORT') ?: '3306', // Fallback to default MySQL port 3306 if DB_PORT is not set
        // A l'IUT, c'est votre login
        // Sur votre machine, vous avez surement un compte 'root'
        'login' => getenv('DB_USER') ?: 'root', // Fallback to 'root' if DB_USER is not set
        // A l'IUT, c'est le même mdp que PhpMyAdmin
        // Sur votre machine personelle, vous avez creez ce mdp a l'installation
        'motDePasse' => getenv('DB_PASS') ?: '' // Fallback to empty string if DB_PASS is not set
    );

    static public function getLogin(): string
    {
        // L'attribut statique $configurationBaseDeDonnees
        // s'obtient avec la syntaxe ConfigurationBaseDeDonnees::$configurationBaseDeDonnees
        // au lieu de $this->configurationBaseDeDonnees pour un attribut non statique
        return ConfigurationBaseDeDonnees::$configurationBaseDeDonnees['login'];
    }

    static public function getNomHote(): string
    {
        return ConfigurationBaseDeDonnees::$configurationBaseDeDonnees['nomHote'];
    }

    static public function getPort(): string
    {
        return ConfigurationBaseDeDonnees::$configurationBaseDeDonnees['port'];
    }

    static public function getNomBaseDeDonnees(): string
    {
        return ConfigurationBaseDeDonnees::$configurationBaseDeDonnees['nomBaseDeDonnees'];
    }

    static public function getPassword(): string
    {
        return ConfigurationBaseDeDonnees::$configurationBaseDeDonnees['motDePasse'];
    }
}

?>

