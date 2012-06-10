<?php

$file = __DIR__.'/../vendor/autoload.php';
if (!file_exists($file)) {
    throw new RuntimeException('Install dependencies to run test suite.');
}

$loader = require_once $file;

// Specify autoloading for Symfony component test classes (2.0 only)
$loader->add('Symfony\Tests',  __DIR__.'/../vendor/symfony/symfony/tests/');

use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;

AnnotationDriver::registerAnnotationClasses();
