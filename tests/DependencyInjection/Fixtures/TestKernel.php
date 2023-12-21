<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection\Fixtures;

use Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Kernel;

use function md5;
use function mt_rand;
use function sys_get_temp_dir;

class TestKernel extends Kernel
{
    private ?string $projectDir = null;

    public function __construct(bool $debug = true)
    {
        parent::__construct('test', $debug);
    }

    /** @return iterable<Bundle> */
    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new DoctrineMongoDBBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(static function (ContainerBuilder $container): void {
            $container->loadFromExtension('framework', ['secret' => 'F00']);

            $container->loadFromExtension('doctrine_mongodb', [
                'connections' => ['default' => []],
                'document_managers' => [
                    'default' => [
                        'mappings' => [
                            'RepositoryServiceBundle' => [
                                'type' => 'attribute',
                                'dir' => __DIR__ . '/Bundles/RepositoryServiceBundle/Document',
                                'prefix' => 'Fixtures\Bundles\RepositoryServiceBundle\Document',
                            ],
                        ],
                    ],
                ],
            ]);

            // Register a NullLogger to avoid getting the stderr default logger of FrameworkBundle
            $container->register('logger', NullLogger::class);
        });
    }

    public function getProjectDir(): string
    {
        if ($this->projectDir === null) {
            $this->projectDir = sys_get_temp_dir() . '/sf_kernel_' . md5((string) mt_rand());
        }

        return $this->projectDir;
    }

    public function getRootDir(): string
    {
        return $this->getProjectDir();
    }
}
