<?php

require_once $_SERVER['SYMFONY'].'/Symfony/Component/ClassLoader/UniversalClassLoader.php';

$loader = new Symfony\Component\ClassLoader\UniversalClassLoader();
$loader->registerNamespace('Symfony\\Bundle\\DoctrineMongoDBBundle\\Tests', __DIR__.'/../../../..');
$loader->registerNamespace('Symfony\\Bundle\\DoctrineMongoDBBundle', __DIR__.'/../../../..');
$loader->registerNamespace('Symfony\\Tests', $_SERVER['SYMFONY_TESTS']);
$loader->registerNamespace('Symfony', $_SERVER['SYMFONY']);
$loader->registerNamespace('Doctrine\\ODM\\MongoDB', $_SERVER['DOCTRINE_MONGODB_ODM']);
$loader->registerNamespace('Doctrine\\MongoDB', $_SERVER['DOCTRINE_MONGODB']);
$loader->registerNamespace('Doctrine\\Common', $_SERVER['DOCTRINE_COMMON']);
$loader->register();
