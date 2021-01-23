<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\DataCollector;

use Doctrine\ODM\MongoDB\APM\Command;
use Doctrine\ODM\MongoDB\APM\CommandLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Throwable;

use function array_map;
use function count;
use function json_encode;

class CommandDataCollector extends DataCollector
{
    /** @var CommandLogger */
    private $commandLogger;

    public function __construct(CommandLogger $commandLogger)
    {
        $this->commandLogger = $commandLogger;
    }

    public function collect(Request $request, Response $response, ?Throwable $exception = null): void
    {
        $this->data = [
            'num_commands' => count($this->commandLogger),
            'commands' => array_map(
                static function (Command $command): string {
                    return json_encode($command->getCommand());
                },
                $this->commandLogger->getAll()
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

    /**
     * @return string[]
     */
    public function getCommands(): array
    {
        return $this->data['commands'];
    }

    public function getName(): string
    {
        return 'mongodb';
    }
}
