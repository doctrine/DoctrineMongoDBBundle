<?php

/*
 * This file is part of the Doctrine MongoDBBundle
 *
 * The code was originally distributed inside the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 * (c) Doctrine Project
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Bundle\MongoDBBundle\Tests\DataCollector;

use Doctrine\Bundle\MongoDBBundle\DataCollector\StandardDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StandardDataCollectorTest extends \PHPUnit_Framework_TestCase
{
    public function testCollect()
    {
        $collector = new StandardDataCollector();
        $collector->logQuery(['foo' => 'bar']);
        $collector->collect(new Request(), new Response());

        $this->assertEquals(1, $collector->getQueryCount());
        $this->assertEquals(['{"foo":"bar"}'], $collector->getQueries());
    }
}
