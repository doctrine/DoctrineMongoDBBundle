<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Compiler;

use Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler\DoctrineMongoDBMappingsPass;
use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;
use Doctrine\ODM\MongoDB\Mapping\Driver\XmlDriver;
use Doctrine\Persistence\Mapping\Driver\PHPDriver;
use Doctrine\Persistence\Mapping\Driver\StaticPHPDriver;
use Doctrine\Persistence\Mapping\Driver\SymfonyFileLocator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class DoctrineMongoDBMappingsPassTest extends TestCase
{
    public function testCreateXmlMappingDriver(): void
    {
        $mappings = ['/path/to/xml' => 'MyNamespace'];
        $pass     = DoctrineMongoDBMappingsPass::createXmlMappingDriver($mappings, []);

        $container = new ContainerBuilder();
        $container->setParameter('doctrine_mongodb.odm.default_document_manager', 'default');
        $container->register('doctrine_mongodb.odm.default_metadata_driver');

        $pass->process($container);

        $chainDriverDef = $container->getDefinition('doctrine_mongodb.odm.default_metadata_driver');
        $methodCalls    = $chainDriverDef->getMethodCalls();

        $this->assertCount(1, $methodCalls);
        $this->assertSame('addDriver', $methodCalls[0][0]);

        $args      = $methodCalls[0][1];
        $driverDef = $args[0];
        $namespace = $args[1];

        $this->assertSame('MyNamespace', $namespace);
        $this->assertInstanceOf(Definition::class, $driverDef);
        $this->assertSame(XmlDriver::class, $driverDef->getClass());

        $locatorDef = $driverDef->getArgument(0);
        $this->assertInstanceOf(Definition::class, $locatorDef);
        $this->assertSame(SymfonyFileLocator::class, $locatorDef->getClass());
        $this->assertSame($mappings, $locatorDef->getArgument(0));
        $this->assertSame('.mongodb.xml', $locatorDef->getArgument(1));
    }

    public function testCreatePhpMappingDriver(): void
    {
        $mappings = ['/path/to/php' => 'MyNamespace'];
        $pass     = DoctrineMongoDBMappingsPass::createPhpMappingDriver($mappings, []);

        $container = new ContainerBuilder();
        $container->setParameter('doctrine_mongodb.odm.default_document_manager', 'default');
        $container->register('doctrine_mongodb.odm.default_metadata_driver');

        $pass->process($container);

        $chainDriverDef = $container->getDefinition('doctrine_mongodb.odm.default_metadata_driver');
        $methodCalls    = $chainDriverDef->getMethodCalls();

        $this->assertCount(1, $methodCalls);
        $this->assertSame('addDriver', $methodCalls[0][0]);

        $args      = $methodCalls[0][1];
        $driverDef = $args[0];
        $namespace = $args[1];

        $this->assertSame('MyNamespace', $namespace);
        $this->assertInstanceOf(Definition::class, $driverDef);
        $this->assertSame(PHPDriver::class, $driverDef->getClass());

        $locatorDef = $driverDef->getArgument(0);
        $this->assertInstanceOf(Definition::class, $locatorDef);
        $this->assertSame(SymfonyFileLocator::class, $locatorDef->getClass());
        $this->assertSame($mappings, $locatorDef->getArgument(0));
    }

    public function testCreateAttributeMappingDriver(): void
    {
        $namespaces  = ['MyNamespace'];
        $directories = ['/path/to/attributes'];
        $pass        = DoctrineMongoDBMappingsPass::createAttributeMappingDriver($namespaces, $directories, []);

        $container = new ContainerBuilder();
        $container->setParameter('doctrine_mongodb.odm.default_document_manager', 'default');
        $container->register('doctrine_mongodb.odm.default_metadata_driver');

        $pass->process($container);

        $chainDriverDef = $container->getDefinition('doctrine_mongodb.odm.default_metadata_driver');
        $methodCalls    = $chainDriverDef->getMethodCalls();

        $this->assertCount(1, $methodCalls);
        $this->assertSame('addDriver', $methodCalls[0][0]);

        $args      = $methodCalls[0][1];
        $driverDef = $args[0];
        $namespace = $args[1];

        $this->assertSame('MyNamespace', $namespace);
        $this->assertInstanceOf(Definition::class, $driverDef);
        $this->assertSame(AttributeDriver::class, $driverDef->getClass());
        $this->assertSame($directories, $driverDef->getArgument(0));
    }

    public function testCreateStaticPhpMappingDriver(): void
    {
        $namespaces  = ['MyNamespace'];
        $directories = ['/path/to/static-php'];
        $pass        = DoctrineMongoDBMappingsPass::createStaticPhpMappingDriver($namespaces, $directories, []);

        $container = new ContainerBuilder();
        $container->setParameter('doctrine_mongodb.odm.default_document_manager', 'default');
        $container->register('doctrine_mongodb.odm.default_metadata_driver');

        $pass->process($container);

        $chainDriverDef = $container->getDefinition('doctrine_mongodb.odm.default_metadata_driver');
        $methodCalls    = $chainDriverDef->getMethodCalls();

        $this->assertCount(1, $methodCalls);
        $this->assertSame('addDriver', $methodCalls[0][0]);

        $args      = $methodCalls[0][1];
        $driverDef = $args[0];
        $namespace = $args[1];

        $this->assertSame('MyNamespace', $namespace);
        $this->assertInstanceOf(Definition::class, $driverDef);
        $this->assertSame(StaticPHPDriver::class, $driverDef->getClass());
        $this->assertSame($directories, $driverDef->getArgument(0));
    }
}
