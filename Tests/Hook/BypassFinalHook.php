<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Hook;

use DG\BypassFinals;
use PHPUnit\Runner\BeforeTestHook;

class BypassFinalHook implements BeforeTestHook
{
    public function executeBeforeTest(string $test) : void
    {
        BypassFinals::enable();
        BypassFinals::setWhitelist(['*\Doctrine\ODM\MongoDB\APM\*']);
    }
}
