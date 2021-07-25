<?php

declare(strict_types=1);

/**
 * @Author: Arend Hummeling
 */

namespace Doctrine\Bundle\MongoDBBundle\Tests\CacheWarmer;

use Doctrine\Bundle\MongoDBBundle\CacheWarmer\MetadataCacheWarmer;
use Doctrine\Bundle\MongoDBBundle\Tests\TestCase;
use Doctrine\Persistence\ObjectManager;

use function sys_get_temp_dir;
use function touch;
use function unlink;

use const DIRECTORY_SEPARATOR;

class MetadataCacheWarmerTest extends TestCase
{
    /** @var ObjectManager $dm */
    private $dm;

    private $phpArrayFileName;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dm               = self::createTestDocumentManager([__DIR__ . '/../Fixtures/Validator']);
        $this->phpArrayFileName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'DoctrineBundleMongoDBBundleTestsFixturesValidatorMetadata.php';
    }

    public function testWarmerNotOptional(): void
    {
        $this->assertFalse($this->getWarmer()->isOptional());
    }

    public function testWarmerExecuted(): void
    {
        try {
            $this->getWarmer()->warmUp('meh');
            self::assertFileExists($this->phpArrayFileName);
        } finally {
            @unlink($this->phpArrayFileName);
        }
    }

    public function testWarmerNotExecuted(): void
    {
        try {
            $warmer = $this->getWarmer();
            touch($this->phpArrayFileName);
            self::assertNull($warmer->warmUp('meh'));
        } finally {
            @unlink($this->phpArrayFileName);
        }
    }

    private function getWarmer(): MetadataCacheWarmer
    {
        return new MetadataCacheWarmer($this->dm, $this->phpArrayFileName);
    }
}
