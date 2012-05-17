<?php

use Doctrine\Common\Annotations\AnnotationRegistry;

if (!$loader = @include __DIR__ . '/../vendor/.composer/autoload.php') {
    die("You must set up the project dependencies, run the following commands:
wget http://getcomposer.org/composer.phar
php composer.phar install
");
}

AnnotationRegistry::registerLoader(function($class) use ($loader) {
    $loader->loadClass($class);
    return class_exists($class, false);
});

AnnotationRegistry::registerFile(__DIR__ . '/../vendor/doctrine/mongodb-odm/lib/Doctrine/ODM/MongoDB/Mapping/Annotations/DoctrineAnnotations.php');
