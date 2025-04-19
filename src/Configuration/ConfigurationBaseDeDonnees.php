<?php
namespace App\Configuration;

class ConfigurationBaseDeDonnees
{

    // Les détails de configuration sont chargés paresseusement lors du premier accès
    static private ?array $configurationBaseDeDonnees = null;

    // Initialise le tableau de configuration s'il n'a pas encore été chargé
    private static function initConfig(): void
    {
        if (self::$configurationBaseDeDonnees === null) {
            self::$configurationBaseDeDonnees = [
                // Le nom d'hote est webinfo a l'IUT
                // ou localhost sur votre machine
                // ou webinfo.iutmontp.univ-montp2.fr pour accéder à webinfo depuis l'extérieur
                'nomHote' => getenv('DB_HOST') ?: 'localhost', // Repli sur 'localhost' si DB_HOST n'est pas défini

                // A l'IUT, vous avez une base de données nommee comme votre login
                // Sur votre machine, vous devrez creer une base de données
                'nomBaseDeDonnees' => getenv('DB_NAME') ?: 'default_db', // Repli si DB_NAME n'est pas défini

                // À l'IUT, le port de MySQL est particulier : 3316
                // Ailleurs, on utilise le port par défaut : 3306
                'port' => getenv('DB_PORT') ?: '3306', // Repli sur le port MySQL par défaut 3306 si DB_PORT n'est pas défini

                // A l'IUT, c'est votre login
                // Sur votre machine, vous avez surement un compte 'root'
                'login' => getenv('DB_USER') ?: 'root', // Repli sur 'root' si DB_USER n'est pas défini

                // A l'IUT, c'est le même mdp que PhpMyAdmin
                // Sur votre machine personelle, vous avez creez ce mdp a l'installation
                'motDePasse' => getenv('DB_PASS') ?: '' // Repli sur une chaîne vide si DB_PASS n'est pas défini
            ];
        }
    }

    static public function getLogin(): string
    {
        self::initConfig();
        return self::$configurationBaseDeDonnees['login'];
    }

    static public function getNomHote(): string
    {
        self::initConfig();
        return self::$configurationBaseDeDonnees['nomHote'];
    }

    static public function getPort(): string
    {
        self::initConfig();
        return self::$configurationBaseDeDonnees['port'];
    }

    static public function getNomBaseDeDonnees(): string
    {
        self::initConfig();
        return self::$configurationBaseDeDonnees['nomBaseDeDonnees'];
    }

    static public function getPassword(): string
    {
        self::initConfig();
        return self::$configurationBaseDeDonnees['motDePasse'];
    }
}

