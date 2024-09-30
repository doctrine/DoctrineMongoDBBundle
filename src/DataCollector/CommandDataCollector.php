<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\DataCollector;

use Doctrine\ODM\MongoDB\APM\Command;
use Doctrine\ODM\MongoDB\APM\CommandLogger;
use MongoDB\BSON\Document;
use stdClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Throwable;

use function array_map;
use function array_reduce;
use function count;
use function json_decode;

/** @internal */
final class CommandDataCollector extends DataCollector
{
    public function __construct(private CommandLogger $commandLogger)
    {
    }

    public function collect(Request $request, Response $response, ?Throwable $exception = null): void
    {
        $this->data = [
            'num_commands' => count($this->commandLogger),
            'commands' => array_map(
                static function (Command $command): array {
                    $dbProperty = '$db';
                    $document   = Document::fromPHP($command->getCommand());

                    return [
                        'database' => $command->getCommand()->$dbProperty ?? '',
                        'command' => json_decode($document->toCanonicalExtendedJSON()),
                        'durationMicros' => $command->getDurationMicros(),
                    ];
                },
                $this->commandLogger->getAll(),
            ),
            'time' => array_reduce(
                $this->commandLogger->getAll(),
                static fn (int $total, Command $command): int => $total + $command->getDurationMicros(),
                0,
            ),
        ];
    }

    public function reset(): void
    {
        $this->commandLogger->clear();
        $this->data = [
            'num_commands' => 0,
            'commands' => [],
        ];
    }

    public function getCommandCount(): int
    {
        return $this->data['num_commands'];
    }

    public function getTime(): int
    {
        return $this->data['time'];
    }

    /** @return array<array{database: string, command: stdClass, durationMicros: int}> */
    public function getCommands(): array
    {
        return $this->data['commands'];
    }

    public function getName(): string
    {
        return 'mongodb';
    }
}
