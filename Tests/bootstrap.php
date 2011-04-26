<?php

require_once $_SERVER['SYMFONY'].'/Symfony/Component/ClassLoader/UniversalClassLoader.php';

use Symfony\Component\ClassLoader\UniversalClassLoader;

$loader = new UniversalClassLoader();
$loader->registerNamespace('Symfony', $_SERVER['SYMFONY']);
$loader->registerNamespace('Doctrine\\Common', $_SERVER['DOCTRINE']);
$loader->registerNamespace('Doctrine\\MongoDB', $_SERVER['DOCTRINE_MONGODB']);
$loader->registerNamespace('Doctrine\\ODM\\MongoDB', $_SERVER['DOCTRINE_MONGODB_ODM']);
$loader->register();

spl_autoload_register(function($class)
{
    if (0 === strpos($class, 'Symfony\\Bundle\\DoctrineMongoDBBundle\\')) {
        $path = implode('/', array_slice(explode('\\', $class), 2)).'.php';
        require_once __DIR__.'/../../'.$path;
        return true;
    }
});
