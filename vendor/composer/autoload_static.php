<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitd5ba820900c2d3893635c8b2cf43ac95
{
    public static $prefixesPsr0 = array (
        'P' => 
        array (
            'Pocket' => 
            array (
                0 => __DIR__ . '/..' . '/djchen/pocket-api-php/src',
            ),
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixesPsr0 = ComposerStaticInitd5ba820900c2d3893635c8b2cf43ac95::$prefixesPsr0;

        }, null, ClassLoader::class);
    }
}
