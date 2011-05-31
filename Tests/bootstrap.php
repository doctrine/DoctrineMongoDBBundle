<?php

require_once $_SERVER['SYMFONY'].'/Symfony/Component/ClassLoader/UniversalClassLoader.php';

$loader = new Symfony\Component\ClassLoader\UniversalClassLoader();
$loader->registerNamespace('Symfony\\Tests', $_SERVER['SYMFONY_TESTS']);
$loader->registerNamespace('Symfony', $_SERVER['SYMFONY']);
$loader->registerNamespace('Doctrine\\ODM\\MongoDB', $_SERVER['DOCTRINE_MONGODB_ODM']);
$loader->registerNamespace('Doctrine\\MongoDB', $_SERVER['DOCTRINE_MONGODB']);
$loader->registerNamespace('Doctrine\\Common', $_SERVER['DOCTRINE_COMMON']);
$loader->register();

spl_autoload_register(function($class)
{
    if (0 === strpos($class, 'Symfony\\Bundle\\DoctrineMongoDBBundle\\') &&
        file_exists($file = __DIR__.'/../'.implode('/', array_slice(explode('\\', $class), 3)).'.php')) {
        require_once $file;
    }
});
