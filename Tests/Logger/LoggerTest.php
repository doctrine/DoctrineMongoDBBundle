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

namespace Doctrine\Bundle\MongoDBBundle\Tests\Logger;

use Doctrine\Bundle\MongoDBBundle\Logger\Logger;
use Psr\Log\LoggerInterface;

class LoggerTest extends \PHPUnit_Framework_TestCase
{
    private $logger;

    protected function setUp()
    {
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    protected function tearDown()
    {
        unset($this->logger);
    }

    public function testLogQuery()
    {
        $query = ['foo' => 'bar'];
        $log = json_encode($query);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with('MongoDB query: '.$log);

        $logger = new Logger($this->logger);
        $logger->logQuery(['foo' => 'bar']);
    }

    public function testMongoBinDataBase64Encoded()
    {
        $binData = new \MongoBinData('data', \MongoBinData::BYTE_ARRAY);
        $query = ['foo' => ['binData' => $binData]];
        $log = json_encode(['foo' => ['binData' => base64_encode($binData->bin)]]);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with('MongoDB query: '.$log);

        $logger = new Logger($this->logger);
        $logger->logQuery($query);
    }

    public function testInfinityAndNanEncoded()
    {
        $query = [
            'foo' => [
                'posInf' => INF,
                'negInf' => -INF,
                'nan' => NAN,
            ],
        ];

        $log = json_encode([
            'foo' => [
                'posInf' => 'Infinity',
                'negInf' => '-Infinity',
                'nan' => 'NaN',
            ],
        ]);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with('MongoDB query: '.$log);

        $logger = new Logger($this->logger);
        $logger->logQuery($query);
    }
}
