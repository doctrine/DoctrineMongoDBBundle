<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests;

use Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\FooBundle\Controller\DocumentValueResolverController;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\FooBundle\Document\User;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\FooBundle\FooBundle;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

use function sys_get_temp_dir;
use function uniqid;

/** @requires function \Symfony\Bridge\Doctrine\Attribute\MapEntity::__construct */
class DocumentValueResolverFunctionalTest extends WebTestCase
{
    public function testWithoutConfiguration(): void
    {
        $client = static::createClient();

        $dm   = static::getContainer()->get(DocumentManager::class);
        $user = new User('user-identifier');

        $dm->persist($user);
        $dm->flush();

        $client->request('GET', '/user/user-identifier');

        $this->assertResponseIsSuccessful();
        $this->assertSame('user-identifier', $client->getResponse()->getContent());

        $dm->remove($user);
    }

    public function testWithConfiguration(): void
    {
        $client = static::createClient();

        $dm   = static::getContainer()->get(DocumentManager::class);
        $user = new User('user-identifier');

        $dm->persist($user);
        $dm->flush();

        $client->request('GET', '/user_with_mapping/user-identifier');

        $this->assertResponseIsSuccessful();
        $this->assertSame('user-identifier', $client->getResponse()->getContent());

        $dm->remove($user);
    }

    protected static function getKernelClass(): string
    {
        return FooTestKernel::class;
    }
}

class FooTestKernel extends Kernel
{
    use MicroKernelTrait;

    private string $randomKey;

    public function __construct()
    {
        $this->randomKey = uniqid('');

        parent::__construct('test', false);
    }

    protected function getContainerClass(): string
    {
        return 'test' . $this->randomKey . parent::getContainerClass();
    }

    public function registerBundles(): array
    {
        return [
            new FrameworkBundle(),
            new DoctrineMongoDBBundle(),
            new FooBundle(),
        ];
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->add('tv_user_show', '/user/{id}')
            ->controller([DocumentValueResolverController::class, 'showUserByDefault']);

        $routes->add('user_with_mapping', '/user_with_mapping/{identifier}')
            ->controller([DocumentValueResolverController::class, 'showUserWithMapping']);
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        $c->loadFromExtension('framework', [
            'secret' => 'foo',
            'router' => ['utf8' => false],
            'http_method_override' => false,
            'test' => true,
        ]);

        $c->loadFromExtension('doctrine_mongodb', [
            'connections' => ['default' => []],
            'document_managers' => [
                'default' => [
                    'mappings' => [
                        'App' => [
                            'is_bundle' => false,
                            'type' => 'attribute',
                            'dir' => '%kernel.project_dir%/Document',
                            'prefix' => 'Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\FooBundle',
                            'alias' => 'Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\FooBundle',
                        ],
                    ],
                ],
            ],
        ]);

        $loader->load(__DIR__ . '/Fixtures/FooBundle/config/services.php');
    }

    public function getProjectDir(): string
    {
        return __DIR__ . '/Fixtures/FooBundle/';
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/doctrine_mongodb_odm_bundle' . $this->randomKey;
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir();
    }
}
