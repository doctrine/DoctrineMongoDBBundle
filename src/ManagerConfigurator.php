<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\Types\Type;

/**
 * Configurator for an DocumentManager
 */
class ManagerConfigurator
{
    /**
     * Construct.
     */
    public function __construct(private array $enabledFilters = [])
    {
    }

    /**
     * Create a connection by name.
     */
    public function configure(DocumentManager $documentManager): void
    {
        $this->enableFilters($documentManager);
    }

    /**
     * Enable filters for an given document manager
     */
    private function enableFilters(DocumentManager $documentManager): void
    {
        if (empty($this->enabledFilters)) {
            return;
        }

        $filterCollection = $documentManager->getFilterCollection();
        foreach ($this->enabledFilters as $filter) {
            $filterCollection->enable($filter);
        }
    }

    /**
     * Loads custom types.
     *
     * @throws MappingException
     */
    public static function loadTypes(array $types): void
    {
        foreach ($types as $typeName => $typeConfig) {
            if (Type::hasType($typeName)) {
                Type::overrideType($typeName, $typeConfig['class']);
            } else {
                Type::addType($typeName, $typeConfig['class']);
            }
        }
    }
}
