<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle;

use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Configurator for an DocumentManager
 */
class ManagerConfigurator
{
    /** @var array */
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
     */
    public function configure(DocumentManager $documentManager)
    {
        $this->enableFilters($documentManager);
    }

    /**
     * Enable filters for an given document manager
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
