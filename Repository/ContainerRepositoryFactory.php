<?php

/*
 * This file is part of the Doctrine MongoDBBundle
 *
 * The code was originally distributed inside the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 * (c) Doctrine Project
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Bundle\MongoDBBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\ServiceRepositoryCompilerPass;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Doctrine\ODM\MongoDB\Repository\RepositoryFactory;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;

/**
 * Fetches repositories from the container or falls back to normal creation.
 */
final class ContainerRepositoryFactory implements RepositoryFactory
{
    private $managedRepositories = [];

    private $container;

    /**
     * @param ContainerInterface $container A service locator containing the repositories
     */
    public function __construct(ContainerInterface $container = null)
    {
        // When DoctrineMongoDBBundle requires Symfony 3.3+, this can be removed
        // and the $container argument can become required.
        if (null === $container && class_exists(ServiceLocatorTagPass::class)) {
            throw new \InvalidArgumentException(sprintf('The first argument of %s::__construct() is required on Symfony 3.3 or higher.', self::class));
        }

        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getRepository(DocumentManager $documentManager, $documentName)
    {
        $metadata             = $documentManager->getClassMetadata($documentName);
        $customRepositoryName = $metadata->customRepositoryClassName;

        if (null !== $customRepositoryName) {
            // fetch from the container
            if ($this->container && $this->container->has($customRepositoryName)) {
                $repository = $this->container->get($customRepositoryName);

                if (! $repository instanceof DocumentRepository) {
                    throw new \RuntimeException(sprintf('The service "%s" must extend DocumentRepository (or a base class, like ServiceDocumentRepository).', $customRepositoryName));
                }

                return $repository;
            }

            // if not in the container but the class/id implements the interface, throw an error
            if (is_a($customRepositoryName, ServiceDocumentRepositoryInterface::class, true)) {
                // can be removed when DoctrineMongoDBBundle requires Symfony 3.3
                if (null === $this->container) {
                    throw new \RuntimeException(sprintf('Support for loading documents from the service container only works for Symfony 3.3 or higher. Upgrade your version of Symfony or make sure your "%s" class does not implement "%s"', $customRepositoryName, ServiceDocumentRepositoryInterface::class));
                }

                throw new \RuntimeException(sprintf('The "%s" document repository implements "%s", but its service could not be found. Make sure the service exists and is tagged with "%s".', $customRepositoryName, ServiceDocumentRepositoryInterface::class, ServiceRepositoryCompilerPass::REPOSITORY_SERVICE_TAG));
            }

            if (! class_exists($customRepositoryName)) {
                throw new \RuntimeException(sprintf('The "%s" document has a repositoryClass set to "%s", but this is not a valid class. Check your class naming. If this is meant to be a service id, make sure this service exists and is tagged with "%s".', $metadata->name, $customRepositoryName, ServiceRepositoryCompilerPass::REPOSITORY_SERVICE_TAG));
            }

            // allow the repository to be created below
        }

        return $this->getOrCreateRepository($documentManager, $metadata);
    }

    private function getOrCreateRepository(DocumentManager $documentManager, ClassMetadata $metadata)
    {
        $repositoryHash = $metadata->getName().spl_object_hash($documentManager);
        if (isset($this->managedRepositories[$repositoryHash])) {
            return $this->managedRepositories[$repositoryHash];
        }

        $repositoryClassName = $metadata->customRepositoryClassName ?: $documentManager->getConfiguration()->getDefaultRepositoryClassName();

        return $this->managedRepositories[$repositoryHash] = new $repositoryClassName($documentManager, $metadata);
    }
}
