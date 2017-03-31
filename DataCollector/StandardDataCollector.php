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

namespace Doctrine\Bundle\MongoDBBundle\DataCollector;

use Doctrine\Bundle\MongoDBBundle\Logger\LoggerInterface;
use Doctrine\Common\EventArgs;
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

    //http://jwage.com/post/30490207842/logging-mongodb-explains-in-symfony2
    public function collectionPostFind(EventArgs $args)
    {
        //get last logged query and add field "explain"
        $c = count($this->queries);
        if (0 === $c) {
            return;
        }

        $i = $c - 1;
        $cursor = $args->getData();
        try {
            $explain = $cursor->explain();
        } catch (\Exception $exception) {
            return;
        }
        $this->queries[$i]["explain"] = $explain;
    }
}
