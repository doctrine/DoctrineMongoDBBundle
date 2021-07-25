<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\CacheWarmer;

use Doctrine\Persistence\Mapping\AbstractClassMetadataFactory;
use Doctrine\Persistence\ObjectManager;
use LogicException;
use Symfony\Bundle\FrameworkBundle\CacheWarmer\AbstractPhpFileCacheWarmer;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\DoctrineProvider;

use function is_file;
use function method_exists;

/** @internal  */
class MetadataCacheWarmer extends AbstractPhpFileCacheWarmer
{
    /** @var ObjectManager */
    private $objectManager;

    /** @var string */
    private $phpArrayFile;

    public function __construct(ObjectManager $objectManager, string $phpArrayFile)
    {
        $this->objectManager = $objectManager;
        $this->phpArrayFile  = $phpArrayFile;

        parent::__construct($phpArrayFile);
    }

    /**
     * It must not be optional because it should be called before ProxyCacheWarmer which is not optional.
     */
    public function isOptional(): bool
    {
        return false;
    }

    protected function doWarmUp(string $cacheDir, ArrayAdapter $arrayAdapter): bool
    {
        // cache already warmed up, no need to do it again
        if (is_file($this->phpArrayFile)) {
            return false;
        }

        $metadataFactory = $this->objectManager->getMetadataFactory();
        if ($metadataFactory instanceof AbstractClassMetadataFactory && $metadataFactory->getLoadedMetadata()) {
            throw new LogicException('DoctrineMetadataCacheWarmer must load metadata first, check priority of your warmers.');
        }

        if (method_exists($metadataFactory, 'setCache')) {
            $metadataFactory->setCache($arrayAdapter);
        } elseif ($metadataFactory instanceof AbstractClassMetadataFactory) {
            // BC with doctrine/persistence < 2.2
            $metadataFactory->setCacheDriver(new DoctrineProvider($arrayAdapter));
        }

        $metadataFactory->getAllMetadata();

        return true;
    }
}
