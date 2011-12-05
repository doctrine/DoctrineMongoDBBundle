<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\DoctrineMongoDBBundle\Tests\DataCollector;

use Symfony\Bundle\DoctrineMongoDBBundle\DataCollector\StandardDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StandardDataCollectorTest extends \PHPUnit_Framework_TestCase
{
    public function testCollect()
    {
        $collector = new StandardDataCollector();
        $collector->logQuery(array('foo' => 'bar'));
        $collector->collect(new Request(), new Response());

        $this->assertEquals(1, $collector->getQueryCount());
        $this->assertEquals(array('{"foo":"bar"}'), $collector->getQueries());
    }
}
