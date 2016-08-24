<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit81035325468a47903d04400b59c959d2
{
    public static $prefixLengthsPsr4 = array (
        'J' => 
        array (
            'JMathai\\PhpMultiCurl\\' => 21,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'JMathai\\PhpMultiCurl\\' => 
        array (
            0 => __DIR__ . '/..' . '/jmathai/php-multi-curl/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit81035325468a47903d04400b59c959d2::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit81035325468a47903d04400b59c959d2::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}