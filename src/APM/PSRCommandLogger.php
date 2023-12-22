<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\APM;

use Doctrine\ODM\MongoDB\APM\CommandLoggerInterface;
use MongoDB\Driver\Monitoring\CommandFailedEvent;
use MongoDB\Driver\Monitoring\CommandStartedEvent;
use MongoDB\Driver\Monitoring\CommandSucceededEvent;
use Psr\Log\LoggerInterface;

use function json_encode;
use function MongoDB\Driver\Monitoring\addSubscriber;
use function MongoDB\Driver\Monitoring\removeSubscriber;

final class PSRCommandLogger implements CommandLoggerInterface
{
    private bool $registered = false;

    public function __construct(private ?LoggerInterface $logger, private string $prefix = 'MongoDB command: ')
    {
    }

    public function register(): void
    {
        if ($this->logger === null || $this->registered) {
            return;
        }

        $this->registered = true;
        addSubscriber($this);
    }

    public function unregister(): void
    {
        if (! $this->registered) {
            return;
        }

        removeSubscriber($this);
        $this->registered = false;
    }

    public function commandStarted(CommandStartedEvent $event): void
    {
        if (! $this->logger) {
            return;
        }

        $this->logger->debug($this->prefix . json_encode($event->getCommand()));
    }

    public function commandSucceeded(CommandSucceededEvent $event): void
    {
    }

    public function commandFailed(CommandFailedEvent $event): void
    {
    }
}
