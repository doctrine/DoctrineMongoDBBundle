<?php


namespace Doctrine\Bundle\MongoDBBundle\Tests\DataCollector;

use Doctrine\Bundle\MongoDBBundle\DataCollector\StandardDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use PHPUnit\Framework\TestCase;

class StandardDataCollectorTest extends TestCase
{
    public function testCollect()
    {
        $collector = new StandardDataCollector();
        $collector->logQuery(['foo' => 'bar']);
        $collector->collect(new Request(), new Response());

        $this->assertEquals(1, $collector->getQueryCount());
        $this->assertEquals(['{"foo":"bar"}'], $collector->getQueries());
    }

    public function testReset()
    {
        $collector = new StandardDataCollector();
        $collector->logQuery(['foo' => 'bar']);
        $collector->collect(new Request(), new Response());

        $collector->reset();
        $collector->collect(new Request(), new Response());

        $this->assertEquals([], $collector->getQueries());
        $this->assertEquals(0, $collector->getQueryCount());
    }
}
