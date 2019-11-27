<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\DataCollector;

use Doctrine\Bundle\MongoDBBundle\DataCollector\CommandDataCollector;
use Doctrine\Bundle\MongoDBBundle\Tests\TestCase;
use Doctrine\ODM\MongoDB\APM\CommandLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CommandDataCollectorTest extends TestCase
{
    public function testCollector()
    {
        $collector = new CommandDataCollector(new CommandLogger());

        $collector->collect($request = new Request(['group' => '0']), $response = new Response());
    }
}
