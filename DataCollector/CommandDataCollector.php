<?php

namespace Doctrine\Bundle\MongoDBBundle\DataCollector;

use Doctrine\ODM\MongoDB\APM\Command;
use Doctrine\ODM\MongoDB\APM\CommandLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

class CommandDataCollector extends DataCollector
{
    private $commandLogger;

    public function __construct(CommandLogger $commandLogger)
    {
        $this->commandLogger = $commandLogger;
    }

    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data = [
            'num_commands' => count($this->commandLogger),
            'commands' => array_map(
                function (Command $command): string {
                    return json_encode($command->getCommand());
                },
                $this->commandLogger->getAll()
            ),
        ];
    }

    public function reset()
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

    public function getName()
    {
        return 'mongodb';
    }
}
