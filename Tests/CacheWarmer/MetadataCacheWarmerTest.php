<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\CacheWarmer;

use Doctrine\Bundle\MongoDBBundle\CacheWarmer\MetadataCacheWarmer;
use Doctrine\Bundle\MongoDBBundle\Tests\TestCase;
use Doctrine\Persistence\ObjectManager;

use function sys_get_temp_dir;
use function tempnam;
use function touch;
use function unlink;

class MetadataCacheWarmerTest extends TestCase
{
    /** @var ObjectManager $dm */
    private $dm;

    /** @var string */
    private static $cacheFile;

    public static function setUpBeforeClass(): void
    {
        self::$cacheFile = tempnam(sys_get_temp_dir(), 'doctrine_mongodb_odm_metadata_cache_warmer_dir');
    }

    public static function tearDownAfterClass(): void
    {
        @unlink(self::$cacheFile);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->dm = self::createTestDocumentManager();
    }

    public function testWarmerNotOptional(): void
    {
        $this->assertFalse($this->getWarmer()->isOptional());
    }

    public function testWarmerExecuted(): void
    {
        $this->getWarmer()->warmUp('meh');
        self::assertFileExists(self::$cacheFile);
    }

    public function testWarmerNotExecuted(): void
    {
        $warmer = $this->getWarmer();
        touch(self::$cacheFile);
        self::assertNull($warmer->warmUp('meh'));
    }

    private function getWarmer(): MetadataCacheWarmer
    {
        return new MetadataCacheWarmer($this->dm, self::$cacheFile);
    }
}
