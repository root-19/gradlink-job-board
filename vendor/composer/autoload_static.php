<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit761fc407f796c0cad9cf709708a5b3b9
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'PHPMailer\\PHPMailer\\' => 20,
        ),
        'A' => 
        array (
            'App\\' => 4,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'PHPMailer\\PHPMailer\\' => 
        array (
            0 => __DIR__ . '/..' . '/phpmailer/phpmailer/src',
        ),
        'App\\' => 
        array (
            0 => __DIR__ . '/../..' . '/app',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit761fc407f796c0cad9cf709708a5b3b9::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit761fc407f796c0cad9cf709708a5b3b9::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit761fc407f796c0cad9cf709708a5b3b9::$classMap;

        }, null, ClassLoader::class);
    }
}
