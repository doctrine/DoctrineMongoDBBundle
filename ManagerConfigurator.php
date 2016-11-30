<?php

/*
 * This file is part of the Doctrine MongoDB Bundle
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Bundle\MongoDBBundle;

use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Configurator for an DocumentManager
 *
 * @author Wesley van Opdorp <wesley.van.opdorp@freshheads.com>
 */
class ManagerConfigurator
{
    private $enabledFilters = [];

    /**
     * Construct.
     *
     * @param array $enabledFilters
     */
    public function __construct(array $enabledFilters)
    {
        $this->enabledFilters = $enabledFilters;
    }

    /**
     * Create a connection by name.
     *
     * @param DocumentManager $documentManager
     */
    public function configure(DocumentManager $documentManager)
    {
        $this->enableFilters($documentManager);
    }

    /**
     * Enable filters for an given document manager
     *
     * @param DocumentManager $documentManager
     *
     * @return null
     */
    private function enableFilters(DocumentManager $documentManager)
    {
        if (empty($this->enabledFilters)) {
            return;
        }

        $filterCollection = $documentManager->getFilterCollection();
        foreach ($this->enabledFilters as $filter) {
            $filterCollection->enable($filter);
        }
    }
}
