<?php


namespace Doctrine\Bundle\MongoDBBundle\Tests\Logger;

use Doctrine\Bundle\MongoDBBundle\Logger\Logger;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
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
        $binData = new \MongoDB\BSON\Binary('data', \MongoDB\BSON\Binary::TYPE_OLD_BINARY);
        $query = ['foo' => ['binData' => $binData]];
        $log = json_encode(['foo' => ['binData' => $binData->getData()]]);

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
