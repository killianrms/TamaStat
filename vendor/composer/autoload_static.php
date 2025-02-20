<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit4849350eba0a6241579dee6161037aeb
{
    public static $prefixLengthsPsr4 = array (
        'A' => 
        array (
            'App\\' => 4,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'App\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit4849350eba0a6241579dee6161037aeb::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit4849350eba0a6241579dee6161037aeb::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit4849350eba0a6241579dee6161037aeb::$classMap;

        }, null, ClassLoader::class);
    }
}
