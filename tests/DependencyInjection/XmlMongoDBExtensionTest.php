<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\RequiresMethod;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/** XML Support was removed from Symfony 8.0 */
#[RequiresMethod(XmlFileLoader::class, 'load')]
class XmlMongoDBExtensionTest extends AbstractMongoDBExtensionTestCase
{
    protected function loadFromFile(ContainerBuilder $container, string $file): void
    {
        $loadXml = new XmlFileLoader($container, new FileLocator(__DIR__ . '/Fixtures/config/xml'));
        $loadXml->load($file . '.xml');
    }
}
