<?php

namespace App\Configuration;

class ConfigurationSite
{
    const DUREE_EXPIRATION_SESSION = 1800;
    private static ConfigurationSite $instance;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->verifierDerniereActivite();
        }
        return self::$instance;
    }

    public static function getURLAbsolue() : string {
        $protocol = isset($_SERVER['HTTPS']) ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $script = $_SERVER['SCRIPT_NAME'];
        return "$protocol://$host$script";
    }
}