<?php


namespace Doctrine\Bundle\MongoDBBundle\DataCollector;

use Doctrine\Bundle\MongoDBBundle\Logger\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * Data collector for the Doctrine MongoDB ODM.
 *
 * @author Kris Wallsmith <kris@symfony.com>
 */
class StandardDataCollector extends DataCollector implements LoggerInterface
{
    protected $queries;

    public function __construct()
    {
        $this->queries = [];
    }

    public function logQuery(array $query)
    {
        $this->queries[] = $query;
    }

    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data['nb_queries'] = count($this->queries);
        $this->data['queries'] = array_map('json_encode', $this->queries);
    }

    public function reset()
    {
        $this->queries = [];
        $this->data = [
            'nb_queries' => 0,
            'queries' => [],
        ];
    }

    public function getQueryCount()
    {
        return $this->data['nb_queries'];
    }

    public function getQueries()
    {
        return $this->data['queries'];
    }

    public function getName()
    {
        return 'mongodb';
    }
}
