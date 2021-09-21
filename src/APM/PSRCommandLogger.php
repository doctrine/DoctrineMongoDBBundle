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
    /** @var bool */
    private $registered = false;

    /** @var LoggerInterface|null */
    private $logger;

    /** @var string */
    private $prefix;

    public function __construct(?LoggerInterface $logger, string $prefix = 'MongoDB command: ')
    {
        $this->logger = $logger;
        $this->prefix = $prefix;
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

    public function commandStarted(CommandStartedEvent $event)
    {
        if (! $this->logger) {
            return;
        }

        $this->logger->debug($this->prefix . json_encode($event->getCommand()));
    }

    public function commandSucceeded(CommandSucceededEvent $event)
    {
    }

    public function commandFailed(CommandFailedEvent $event)
    {
    }
}
