<?php
namespace App\Configuration;

class ConfigurationBaseDeDonnees
{

    // Database credentials are now loaded from the .env file
    // Ensure vlucas/phpdotenv is installed and loaded in your application entry point.

    static public function getLogin(): string
    {
        return getenv('DB_USER') ?: ''; // Provide a default empty string if not set
    }

    static public function getNomHote(): string
    {
        return getenv('DB_HOST') ?: '';
    }

    static public function getPort(): string
    {
        return getenv('DB_PORT') ?: '3306'; // Default MySQL port if not set
    }

    static public function getNomBaseDeDonnees(): string
    {
        return getenv('DB_NAME') ?: '';
    }

    static public function getPassword(): string
    {
        return getenv('DB_PASS') ?: '';
    }
}

?>

