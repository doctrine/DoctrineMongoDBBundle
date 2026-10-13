<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Attribute;

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;
use Doctrine\Bundle\MongoDBBundle\Attribute\MapDocument;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MapDocumentTest extends TestCase
{
    public function testExprAcceptsClosure(): void
    {
        if (! self::supportsClosureExpr()) {
            self::markTestSkipped('Closures in #[MapEntity] require symfony/doctrine-bridge 8.2 or higher.');
        }

        $closure   = static fn () => null;
        $attribute = new MapDocument(expr: $closure);

        self::assertSame($closure, $attribute->expr);
    }

    public function testExprClosureRequiresSymfony82(): void
    {
        if (self::supportsClosureExpr()) {
            self::markTestSkipped('symfony/doctrine-bridge 8.2 supports closures in #[MapEntity].');
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('requires symfony/doctrine-bridge 8.2 or higher');

        new MapDocument(expr: static fn () => null);
    }

    private static function supportsClosureExpr(): bool
    {
        return InstalledVersions::satisfies(new VersionParser(), 'symfony/doctrine-bridge', '>=8.2');
    }
}
