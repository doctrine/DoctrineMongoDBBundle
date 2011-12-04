<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\DoctrineMongoDBBundle\Logger;

use Symfony\Component\HttpKernel\Log\LoggerInterface as SymfonyLogger;

/**
 * A lightweight query logger.
 * 
 * @author Kris Wallsmith <kris@symfony.com>
 */
class StandardLogger implements LoggerInterface
{
    private $logger;
    private $prefix;
    private $queries;

    public function __construct(SymfonyLogger $logger = null, $prefix = 'MongoDB query: ')
    {
        $this->logger = $logger;
        $this->prefix = $prefix;
        $this->queries = array();
    }

    public function logQuery(array $query)
    {
        if (isset($query['batchInsert']) && 3 < $query['num']) {
            $query['data'] = '('.$query['num'].' items)';
        }

        $this->queries[] = $log = json_encode($query);

        if (null !== $this->logger) {
            $this->logger->info($this->prefix.$log);
        }
    }

    public function getNbQueries()
    {
        return count($this->queries);
    }

    public function getQueries()
    {
        return $this->queries;
    }
}
