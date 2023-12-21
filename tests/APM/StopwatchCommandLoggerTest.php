<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\APM;

use Doctrine\Bundle\MongoDBBundle\APM\StopwatchCommandLogger;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form\Category;
use Doctrine\Bundle\MongoDBBundle\Tests\TestCase;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Stopwatch\Stopwatch;

class StopwatchCommandLoggerTest extends TestCase
{
    private StopwatchCommandLogger $commandLogger;
    private Stopwatch $stopwatch;
    private DocumentManager $dm;

    protected function setUp(): void
    {
        $this->dm = TestCase::createTestDocumentManager();

        $this->stopwatch     = new Stopwatch(true);
        $this->commandLogger = new StopwatchCommandLogger($this->stopwatch);
        $this->commandLogger->register();

        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->commandLogger->unregister();

        $this->dm->getDocumentCollection(Category::class)->drop();

        parent::tearDown();
    }

    public function testItLogsStopwatchEvents(): void
    {
        $category = new Category('one');

        $this->dm->persist($category);
        $this->dm->flush();

        $this->dm->remove($category);
        $this->dm->flush();

        $this->dm->getRepository(Category::class)->findAll();
        $events = $this->stopwatch->getSectionEvents('__root__');

        self::assertCount(3, $events);

        foreach ($events as $eventName => $stopwatchEvent) {
            self::assertMatchesRegularExpression('/mongodb_\d+/', $eventName);
            self::assertGreaterThan(0, $stopwatchEvent->getDuration());
            self::assertSame('doctrine_mongodb', $stopwatchEvent->getCategory());
        }
    }
}
