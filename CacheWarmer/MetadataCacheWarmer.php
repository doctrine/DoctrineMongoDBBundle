<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\CacheWarmer;

use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\CacheWarmer\AbstractPhpFileCacheWarmer;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

use function is_file;

/** @internal */
final class MetadataCacheWarmer extends AbstractPhpFileCacheWarmer
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

    /** @param string $cacheDir */
    protected function doWarmUp($cacheDir, ArrayAdapter $arrayAdapter): bool
    {
        // cache already warmed up, no need to do it again
        if (is_file($this->phpArrayFile)) {
            return false;
        }

        $this->objectManager->getMetadataFactory()->getAllMetadata();

        return true;
    }
}
