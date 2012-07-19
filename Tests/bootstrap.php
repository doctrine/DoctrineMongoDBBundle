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

register_shutdown_function(function() {
    try {
        $mongo = new Mongo();
        $mongo->doctrine->drop();
    } catch (\MongoException $e) {
    }
});

/* Driver version 1.2.11 deprecated setSlaveOkay() in anticipation of connection
 * read preferences. Ignore these warnings until read preferences are
 * implemented.
 */
if (0 >= version_compare('1.2.11', \Mongo::VERSION)) {
    error_reporting(error_reporting() ^ E_DEPRECATED);
}
