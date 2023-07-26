<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Command;

use Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler\FixturesCompilerPass;
use Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\CommandBundle\CommandBundle;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\CommandBundle\DataFixtures\OtherFixtures;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\CommandBundle\DataFixtures\UserFixtures;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RouteConfigurator;

use function sys_get_temp_dir;

final class CommandTestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): array
    {
        return [
            new FrameworkBundle(),
            new DoctrineMongoDBBundle(),
            new CommandBundle(),
        ];
    }

    public function configureRoutes(RouteConfigurator $routes): void
    {
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->loadFromExtension('framework', [
            'secret' => 'foo',
            'router' => ['utf8' => false],
            'http_method_override' => false,
        ]);

        $container->loadFromExtension('doctrine_mongodb', [
            'connections' => ['default' => []],
            'document_managers' => [
                'command_test' => [
                    'connection' => 'default',
                    'mappings' => ['CommandBundle' => null],
                ],
                'command_test_without_documents' => ['connection' => 'default'],
            ],
        ]);

        $container
            ->autowire(UserFixtures::class)
            ->addTag(FixturesCompilerPass::FIXTURE_TAG, ['group' => 'test_group']);

        $container
            ->autowire(OtherFixtures::class)
            ->addTag(FixturesCompilerPass::FIXTURE_TAG);
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/doctrine_mongodb_odm_bundle';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir();
    }
}
