<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle;

use Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler\CreateHydratorDirectoryPass;
use Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler\CreateProxyDirectoryPass;
use Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler\FixturesCompilerPass;
use Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler\ServiceRepositoryCompilerPass;
use Doctrine\Bundle\MongoDBBundle\DependencyInjection\DoctrineMongoDBExtension;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bridge\Doctrine\DependencyInjection\CompilerPass\DoctrineValidationPass;
use Symfony\Bridge\Doctrine\DependencyInjection\CompilerPass\RegisterEventListenersAndSubscribersPass;
use Symfony\Bridge\Doctrine\DependencyInjection\Security\UserProvider\EntityFactory;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use function assert;
use function dirname;
use function method_exists;
use function spl_autoload_register;
use function spl_autoload_unregister;

/**
 * Doctrine MongoDB ODM bundle.
 */
class DoctrineMongoDBBundle extends Bundle
{
    /** @var callable|null */
    private $autoloader;

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new RegisterEventListenersAndSubscribersPass('doctrine_mongodb.odm.connections', 'doctrine_mongodb.odm.%s_connection.event_manager', 'doctrine_mongodb.odm'), PassConfig::TYPE_BEFORE_OPTIMIZATION);

        // runs before CachePoolPass, so that the pool gets its own namespace
        $container->addCompilerPass(new class () implements CompilerPassInterface {
            public function process(ContainerBuilder $container): void
            {
                if (! $container->has('cache.system') || ! $container->hasDefinition('doctrine_mongodb.odm.document_value_resolver.expression_language')) {
                    return;
                }

                $container->setDefinition('cache.doctrine_mongodb.odm.document_value_resolver.expression_language', new ChildDefinition('cache.system'))
                    ->addTag('cache.pool');
            }
        }, PassConfig::TYPE_BEFORE_OPTIMIZATION, 33);

        $container->addCompilerPass(new CreateProxyDirectoryPass(), PassConfig::TYPE_BEFORE_REMOVING);
        $container->addCompilerPass(new CreateHydratorDirectoryPass(), PassConfig::TYPE_BEFORE_REMOVING);
        $container->addCompilerPass(new DoctrineValidationPass('mongodb'));
        $container->addCompilerPass(new ServiceRepositoryCompilerPass());
        $container->addCompilerPass(new FixturesCompilerPass());

        if (! $container->hasExtension('security')) {
            return;
        }

        $security = $container->getExtension('security');

        if (! ($security instanceof SecurityExtension)) {
            return;
        }

        $security->addUserProviderFactory(new EntityFactory('mongodb', 'doctrine_mongodb.odm.security.user.provider'));
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new DoctrineMongoDBExtension();
    }

    public function getPath(): string
    {
        return dirname(__DIR__);
    }

    public function boot(): void
    {
        $registry = $this->container->get('doctrine_mongodb');
        assert($registry instanceof ManagerRegistry);

        $this->registerAutoloader($registry->getManager());
        $this->registerCommandLoggers();
    }

    private function registerAutoloader(DocumentManager $documentManager): void
    {
        $config = $documentManager->getConfiguration();
        // Lazy ghost and native lazy have been added in mongodb-odm 2.10 and 2.14 respectively
        if (
            // @phpstan-ignore-next-line function.alreadyNarrowedType
            method_exists($config, 'isNativeLazyObjectEnabled') && $config->isNativeLazyObjectEnabled()
            // @phpstan-ignore-next-line function.alreadyNarrowedType
            || method_exists($config, 'isLazyGhostObjectEnabled') && $config->isLazyGhostObjectEnabled()
        ) {
            return;
        }

        if ($config->getAutoGenerateProxyClasses() !== Configuration::AUTOGENERATE_FILE_NOT_EXISTS) {
            return;
        }

        $this->autoloader = $config->getProxyManagerConfiguration()->getProxyAutoloader();

        spl_autoload_register($this->autoloader);
    }

    private function unregisterAutoloader(): void
    {
        if ($this->autoloader === null) {
            return;
        }

        spl_autoload_unregister($this->autoloader);
        $this->autoloader = null;
    }

    private function registerCommandLoggers(): void
    {
        $commandLoggerRegistry = $this->container->get('doctrine_mongodb.odm.command_logger_registry');
        $commandLoggerRegistry->register();
    }

    private function unregisterCommandLoggers(): void
    {
        $commandLoggerRegistry = $this->container->get('doctrine_mongodb.odm.command_logger_registry');
        $commandLoggerRegistry->unregister();
    }

    public function shutdown(): void
    {
        $this->unregisterAutoloader();
        $this->unregisterCommandLoggers();
    }
}
