<?php
namespace App\Lib;

class MotDePasse
{
    public static function hash($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public static function verify($password, $hash)
    {
        return password_verify($password, $hash);
    }
}