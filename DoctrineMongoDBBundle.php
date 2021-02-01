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
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use function assert;
use function spl_autoload_register;
use function spl_autoload_unregister;

/**
 * Doctrine MongoDB ODM bundle.
 */
class DoctrineMongoDBBundle extends Bundle
{
    /** @var callable|null */
    private $autoloader;

    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new RegisterEventListenersAndSubscribersPass('doctrine_mongodb.odm.connections', 'doctrine_mongodb.odm.%s_connection.event_manager', 'doctrine_mongodb.odm'), PassConfig::TYPE_BEFORE_OPTIMIZATION);
        $container->addCompilerPass(new CreateProxyDirectoryPass(), PassConfig::TYPE_BEFORE_REMOVING);
        $container->addCompilerPass(new CreateHydratorDirectoryPass(), PassConfig::TYPE_BEFORE_REMOVING);
        $container->addCompilerPass(new DoctrineValidationPass('mongodb'));
        $container->addCompilerPass(new ServiceRepositoryCompilerPass());
        $container->addCompilerPass(new FixturesCompilerPass());

        if (! $container->hasExtension('security')) {
            return;
        }

        $container->getExtension('security')->addUserProviderFactory(new EntityFactory('mongodb', 'doctrine_mongodb.odm.security.user.provider'));
    }

    /**
     * {@inheritDoc}
     */
    public function getContainerExtension()
    {
        return new DoctrineMongoDBExtension();
    }

    public function boot()
    {
        $registry = $this->container->get('doctrine_mongodb');
        assert($registry instanceof ManagerRegistry);

        $this->registerAutoloader($registry->getManager());
        $this->registerCommandLoggers();
    }

    private function registerAutoloader(DocumentManager $documentManager): void
    {
        $configuration = $documentManager->getConfiguration();
        if ($configuration->getAutoGenerateProxyClasses() !== Configuration::AUTOGENERATE_FILE_NOT_EXISTS) {
            return;
        }

        $this->autoloader = $configuration->getProxyManagerConfiguration()->getProxyAutoloader();

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

    public function shutdown()
    {
        $this->unregisterAutoloader();
        $this->unregisterCommandLoggers();
    }
}
