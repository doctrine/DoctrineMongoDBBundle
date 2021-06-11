<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\DataCollector;

use Doctrine\Bundle\MongoDBBundle\DataCollector\CommandDataCollector;
use Doctrine\Bundle\MongoDBBundle\Tests\TestCase;
use Doctrine\ODM\MongoDB\APM\Command;
use Doctrine\ODM\MongoDB\APM\CommandLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function count;

class CommandDataCollectorTest extends TestCase
{
    public function testCollector(): void
    {
        $commandLogger = $this->createCommandLogger([
            $this->createCommand(['first' => 'firstCommand'], 100),
            $this->createCommand(['second' => 'secondCommand'], 200),
        ]);

        $collector = new CommandDataCollector($commandLogger);
        $collector->collect(new Request(), new Response());

        self::assertSame(300, $collector->getTime());
        self::assertSame(2, $collector->getCommandCount());
        self::assertSame(100, $collector->getCommands()[0]['durationMicros']);
        self::assertSame('firstCommand', $collector->getCommands()[0]['command']->first);
        self::assertSame(200, $collector->getCommands()[1]['durationMicros']);
        self::assertSame('secondCommand', $collector->getCommands()[1]['command']->second);
    }

    private function createCommandLogger(array $commands) : CommandLogger
    {
        $commandLoggerMock = $this->createMock(CommandLogger::class);
        $commandLoggerMock->method('count')->willReturn(count($commands));
        $commandLoggerMock->method('getAll')->willReturn($commands);

        return $commandLoggerMock;
    }

    private function createCommand(array $command, int $durationMicros) : Command
    {
        $commandMock = $this->createMock(Command::class);
        $commandMock->method('getCommand')->willReturn((object) $command);
        $commandMock->method('getDurationMicros')->willReturn($durationMicros);

        return $commandMock;
    }
}
