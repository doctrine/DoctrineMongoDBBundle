<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\APM;

use Doctrine\ODM\MongoDB\APM\CommandLoggerInterface;

use function array_map;

final class CommandLoggerRegistry
{
    /** @var CommandLoggerInterface[] */
    private array $commandLoggers = [];

    public function __construct(iterable $commandLoggers)
    {
        foreach ($commandLoggers as $commandLogger) {
            $this->addLogger($commandLogger);
        }
    }

    public function register(): void
    {
        array_map(static function (CommandLoggerInterface $commandLogger): void {
            $commandLogger->register();
        }, $this->commandLoggers);
    }

    public function unregister(): void
    {
        array_map(static function (CommandLoggerInterface $commandLogger): void {
            $commandLogger->unregister();
        }, $this->commandLoggers);
    }

    private function addLogger(CommandLoggerInterface $logger): void
    {
        $this->commandLoggers[] = $logger;
    }
}
