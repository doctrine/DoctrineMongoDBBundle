<?php

/*
 * This file is part of the Doctrine MongoDBBundle
 *
 * The code was originally distributed inside the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 * (c) Doctrine Project
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Bundle\MongoDBBundle\Logger;

/**
 * An aggregate query logger.
 *
 * @author Kris Wallsmith <kris@symfony.com>
 */
class AggregateLogger implements LoggerInterface
{
    private $loggers;

    /**
     * Constructor.
     *
     * @param array $loggers An array of LoggerInterface objects
     */
    public function __construct(array $loggers)
    {
        $this->loggers = $loggers;
    }

    public function logQuery(array $query)
    {
        foreach ($this->loggers as $logger) {
            $logger->logQuery($query);
        }
    }
}
