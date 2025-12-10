<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\Types\Type;
use Doctrine\ODM\MongoDB\Types\TypeRegistry;

use function class_exists;

/**
 * Configurator for a DocumentManager
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
     * Enable filters for a given document manager
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
     * @param array<string, array{class?: class-string<Type>, service?: Type}> $types
     *
     * @throws MappingException
     */
    public static function loadTypes(array $types): void
    {
        // TypeRegistry was introduced in MongoDB ODM 2.16
        if (class_exists(TypeRegistry::class)) {
            $registry = TypeRegistry::getSharedInstance();
            foreach ($types as $typeName => $typeConfig) {
                $registry->register($typeName, $typeConfig['class'] ?? $typeConfig['service'] ?? throw new MappingException('Type class or service must be provided'));
            }

            return;
        }

        foreach ($types as $typeName => $typeConfig) {
            if (! isset($typeConfig['class'])) {
                throw new MappingException('Type "class" must be provided for ODM versions prior to 2.16');
            }

            if (Type::hasType($typeName)) {
                Type::overrideType($typeName, $typeConfig['class']);
            } else {
                Type::addType($typeName, $typeConfig['class']);
            }
        }
    }
}
