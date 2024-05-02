<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInitfdb6d7af3534698ecec341b05f2d39da
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     */
    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        spl_autoload_register(array('ComposerAutoloaderInitfdb6d7af3534698ecec341b05f2d39da', 'loadClassLoader'), true, false);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInitfdb6d7af3534698ecec341b05f2d39da', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Composer\Autoload\ComposerStaticInitfdb6d7af3534698ecec341b05f2d39da::getInitializer($loader));

        $loader->register(false);

        $includeFiles = \Composer\Autoload\ComposerStaticInitfdb6d7af3534698ecec341b05f2d39da::$files;
        foreach ($includeFiles as $fileIdentifier => $file) {
            composerRequirefdb6d7af3534698ecec341b05f2d39da($fileIdentifier, $file);
        }

        return $loader;
    }
}

/**
 * @param string $fileIdentifier
 * @param string $file
 * @return void
 */
function composerRequirefdb6d7af3534698ecec341b05f2d39da($fileIdentifier, $file)
{
    if (empty($GLOBALS['__composer_autoload_files'][$fileIdentifier])) {
        $GLOBALS['__composer_autoload_files'][$fileIdentifier] = true;

        require $file;
    }
}
