<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle;

use Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler\CreateHydratorDirectoryPass;
use Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler\CreateProxyDirectoryPass;
use Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler\ServiceRepositoryCompilerPass;
use Doctrine\Bundle\MongoDBBundle\DependencyInjection\DoctrineMongoDBExtension;
use Symfony\Bridge\Doctrine\DependencyInjection\CompilerPass\DoctrineValidationPass;
use Symfony\Bridge\Doctrine\DependencyInjection\CompilerPass\RegisterEventListenersAndSubscribersPass;
use Symfony\Bridge\Doctrine\DependencyInjection\Security\UserProvider\EntityFactory;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use const DIRECTORY_SEPARATOR;
use function clearstatcache;
use function is_file;
use function spl_autoload_register;
use function spl_autoload_unregister;
use function str_replace;
use function strlen;
use function strpos;
use function substr;

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
        // Register an autoloader for proxies to avoid issues when unserializing them
        // when the ODM is used.
        if (! $this->container->hasParameter('doctrine_mongodb.odm.proxy_namespace')) {
            return;
        }

        $namespace = $this->container->getParameter('doctrine_mongodb.odm.proxy_namespace');
        $dir       = $this->container->getParameter('doctrine_mongodb.odm.proxy_dir');
        // See https://github.com/symfony/symfony/pull/3419 for usage of
        // references
        $container =& $this->container;

        $this->autoloader = static function ($class) use ($namespace, $dir, &$container) {
            if (strpos($class, $namespace) !== 0) {
                return;
            }

            $fileName = str_replace('\\', '', substr($class, strlen($namespace) +1));
            $file     = $dir . DIRECTORY_SEPARATOR . $fileName . '.php';

            if (! is_file($file) && $container->getParameter('doctrine_mongodb.odm.auto_generate_proxy_classes')) {
                $registry = $container->get('doctrine_mongodb');

                // Tries to auto-generate the proxy file
                foreach ($registry->getManagers() as $dm) {
                    if (! $dm->getConfiguration()->getAutoGenerateProxyClasses()) {
                        continue;
                    }

                    $originalClassName = $dm->getClassNameResolver()->getRealClass($class);
                    $classes           = $dm->getMetadataFactory()->getAllMetadata();

                    foreach ($classes as $classMetadata) {
                        if ($classMetadata->name !== $originalClassName) {
                            continue;
                        }

                        $dm->getProxyFactory()->generateProxyClasses([$classMetadata]);
                    }
                }

                clearstatcache(true, $file);
            }

            if (! is_file($file)) {
                return;
            }

            require $file;
        };
        spl_autoload_register($this->autoloader);
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
