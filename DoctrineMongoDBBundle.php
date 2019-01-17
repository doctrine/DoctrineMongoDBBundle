<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle;

use Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler\CreateHydratorDirectoryPass;
use Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler\CreateProxyDirectoryPass;
use Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler\ServiceRepositoryCompilerPass;
use Doctrine\Bundle\MongoDBBundle\DependencyInjection\DoctrineMongoDBExtension;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Types\Type;
use Symfony\Bridge\Doctrine\DependencyInjection\CompilerPass\DoctrineValidationPass;
use Symfony\Bridge\Doctrine\DependencyInjection\CompilerPass\RegisterEventListenersAndSubscribersPass;
use Symfony\Bridge\Doctrine\DependencyInjection\Security\UserProvider\EntityFactory;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
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

        if (! $container->hasExtension('security')) {
            return;
        }

        $container->getExtension('security')->addUserProviderFactory(new EntityFactory('mongodb', 'doctrine_mongodb.odm.security.user.provider'));
    }

    public function getContainerExtension()
    {
        return new DoctrineMongoDBExtension();
    }

    public function boot()
    {
        $this->registerTypes();

        /** @var ManagerRegistry $registry */
        $registry = $this->container->get('doctrine_mongodb');

        $this->registerAutoloader($registry->getManager());
    }

    private function registerAutoloader(DocumentManager $documentManager) : void
    {
        $configuration = $documentManager->getConfiguration();
        if ($configuration->getAutoGenerateProxyClasses() !== Configuration::AUTOGENERATE_FILE_NOT_EXISTS) {
            return;
        }

        $this->autoloader = $configuration->getProxyManagerConfiguration()->getProxyAutoloader();

        spl_autoload_register($this->autoloader);
    }

    private function registerTypes() : void
    {
        if (! $this->container->hasParameter('doctrine_mongodb.odm.types')) {
            // no types defined
            exit;
        }

        $types = $this->container->getParameter('doctrine_mongodb.odm.types');
        foreach ($types as $key => $fqcn) {
            if (Type::hasType($key)) {
                Type::overrideType($key, $fqcn);
            } else {
                Type::addType($key, $fqcn);
            }
        }
    }

    public function shutdown()
    {
        if ($this->autoloader === null) {
            return;
        }

        spl_autoload_unregister($this->autoloader);
        $this->autoloader = null;
    }
}
