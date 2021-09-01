<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Compiler;

use Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures\TestKernel;
use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class CacheCompatibilityPassTest extends TestCase
{
    use ExpectDeprecationTrait;

    /**
     * @group legacy
     * @doesNotPerformAssertions
     */
    public function testMetadataCacheConfigUsingPsr6ServiceDefinedByApplication(): void
    {
        (new class (false) extends TestKernel {
            public function registerContainerConfiguration(LoaderInterface $loader): void
            {
                parent::registerContainerConfiguration($loader);
                $loader->load(static function (ContainerBuilder $containerBuilder): void {
                    $containerBuilder->loadFromExtension(
                        'doctrine_mongodb',
                        ['document_managers' => ['default' => ['metadata_cache_driver' => ['type' => 'service', 'id' => 'custom_cache_service']]]]
                    );
                    $containerBuilder->setDefinition(
                        'custom_cache_service',
                        new Definition(ArrayAdapter::class)
                    );
                });
            }
        })->boot();
    }

    /**
     * @group legacy
     */
    public function testMetadataCacheConfigUsingNonPsr6ServiceDefinedByApplication(): void
    {
        $this->expectDeprecation('Since doctrine/mongodb-odm-bundle 4.4: Configuring doctrine/cache is deprecated. Please update the cache service "custom_cache_service" to use a PSR-6 cache.');
        (new class (false) extends TestKernel {
            public function registerContainerConfiguration(LoaderInterface $loader): void
            {
                parent::registerContainerConfiguration($loader);
                $loader->load(static function (ContainerBuilder $containerBuilder): void {
                    $containerBuilder->loadFromExtension(
                        'doctrine_mongodb',
                        ['document_managers' => ['default' => ['metadata_cache_driver' => ['type' => 'service', 'id' => 'custom_cache_service']]]]
                    );
                    $containerBuilder->setDefinition(
                        'custom_cache_service',
                        (new Definition(DoctrineProvider::class))
                            ->setArguments([new Definition(ArrayAdapter::class)])
                            ->setFactory([DoctrineProvider::class, 'wrap'])
                    );
                });
            }
        })->boot();
    }
}
