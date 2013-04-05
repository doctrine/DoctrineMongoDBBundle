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

use Symfony\Component\HttpKernel\Log\LoggerInterface as SymfonyLogger;

/**
 * A lightweight query logger.
 *
 * @author Kris Wallsmith <kris@symfony.com>
 */
class Logger implements LoggerInterface
{
    private $logger;
    private $prefix;
    private $batchInsertTreshold;

    public function __construct(SymfonyLogger $logger = null, $prefix = 'MongoDB query: ')
    {
        $this->logger = $logger;
        $this->prefix = $prefix;
    }

    public function setBatchInsertThreshold($batchInsertTreshold)
    {
        $this->batchInsertTreshold = $batchInsertTreshold;
    }

    public function logQuery(array $query)
    {
        if (null === $this->logger) {
            return;
        }

        if (isset($query['batchInsert']) && null !== $this->batchInsertTreshold && $this->batchInsertTreshold <= $query['num']) {
            $query['data'] = '**'.$query['num'].' item(s)**';
        }

        array_walk_recursive($query, function(&$value, $key) {
            if ($value instanceof \MongoBinData) {
                $value = base64_encode($value->bin);
                return;
            }
            if (is_float($value) && is_infinite($value)) {
                $value = ($value < 0 ? '-' : '') . 'Infinity';
                return;
            }
            if (is_float($value) && is_nan($value)) {
                $value = 'NaN';
                return;
            }
        });

        $this->logger->info($this->prefix.json_encode($query));
    }
}
