<?php
namespace App\Configuration;

class ConfigurationBaseDeDonnees
{

    // Database credentials are now loaded from the .env file
    // Ensure vlucas/phpdotenv is installed and loaded in your application entry point.

    static public function getLogin(): string
    {
        // TODO: Replace with your actual database username
        return 'YOUR_DB_USER_HERE';
    }

    static public function getNomHote(): string
    {
        // TODO: Replace with your actual database host
        return 'YOUR_DB_HOST_HERE';
    }

    static public function getPort(): string
    {
        // TODO: Replace with your actual database port
        return 'YOUR_DB_PORT_HERE'; // e.g., '3306'
    }

    static public function getNomBaseDeDonnees(): string
    {
        // TODO: Replace with your actual database name
        return 'YOUR_DB_NAME_HERE';
    }

    static public function getPassword(): string
    {
        // TODO: Replace with your actual database password
        return 'YOUR_DB_PASSWORD_HERE';
    }
}

?>

