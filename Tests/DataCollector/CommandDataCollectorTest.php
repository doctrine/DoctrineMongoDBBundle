<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\DataCollector;

use Doctrine\Bundle\MongoDBBundle\DataCollector\CommandDataCollector;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form\Category;
use Doctrine\Bundle\MongoDBBundle\Tests\TestCase;
use Doctrine\ODM\MongoDB\APM\CommandLogger;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CommandDataCollectorTest extends TestCase
{
    private CommandLogger $commandLogger;
    private DocumentManager $dm;

    protected function setUp(): void
    {
        $this->dm = TestCase::createTestDocumentManager();

        $this->commandLogger = new CommandLogger();
        $this->commandLogger->register();

        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->commandLogger->unregister();

        $this->dm->getDocumentCollection(Category::class)->drop();

        parent::tearDown();
    }

    public function testCollector(): void
    {
        $category = new Category('one');

        $this->dm->persist($category);
        $this->dm->flush();

        $this->dm->remove($category);
        $this->dm->flush();

        $this->dm->getRepository(Category::class)->findAll();

        $collector = new CommandDataCollector($this->commandLogger);
        $collector->collect(new Request(), new Response());

        self::assertSame(3, $collector->getCommandCount());
        self::assertGreaterThan(0, $collector->getTime());
        self::assertSame('Category', $collector->getCommands()[0]['command']->insert);
        self::assertGreaterThan(0, $collector->getCommands()[0]['durationMicros']);
        self::assertSame('Category', $collector->getCommands()[1]['command']->delete);
        self::assertGreaterThan(0, $collector->getCommands()[1]['durationMicros']);
        self::assertSame('Category', $collector->getCommands()[2]['command']->find);
        self::assertGreaterThan(0, $collector->getCommands()[2]['durationMicros']);
    }
}
