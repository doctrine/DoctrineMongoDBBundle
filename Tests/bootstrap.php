<?php

$file = __DIR__.'/../vendor/autoload.php';
if (!file_exists($file)) {
    throw new RuntimeException('Install dependencies to run test suite.');
}

require_once $file;

use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;

AnnotationDriver::registerAnnotationClasses();

/* Driver version 1.2.11 deprecated setSlaveOkay() in anticipation of connection
 * read preferences. Ignore these warnings until read preferences are
 * implemented.
 */
if (0 >= version_compare('1.2.11', \Mongo::VERSION)) {
    error_reporting(error_reporting() ^ E_DEPRECATED);
}
