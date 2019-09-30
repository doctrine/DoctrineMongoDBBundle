<?php

namespace Doctrine\Bundle\MongoDBBundle\Repository;

use Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler\ServiceRepositoryCompilerPass;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Doctrine\ODM\MongoDB\Repository\RepositoryFactory;
use Psr\Container\ContainerInterface;

/**
 * Fetches repositories from the container or falls back to normal creation.
 */
final class ContainerRepositoryFactory implements RepositoryFactory
{
    /** @var ObjectRepository[] */
    private $managedRepositories = [];

    /** @var ContainerInterface|null */
    private $container;

    /**
     * @param ContainerInterface $container A service locator containing the repositories
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getRepository(DocumentManager $documentManager, $documentName)
    {
        $metadata             = $documentManager->getClassMetadata($documentName);
        $customRepositoryName = $metadata->customRepositoryClassName;

        if ($customRepositoryName !== null) {
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
        $repositoryHash = $metadata->getName() . spl_object_hash($documentManager);
        if (isset($this->managedRepositories[$repositoryHash])) {
            return $this->managedRepositories[$repositoryHash];
        }

        $repositoryClassName = $metadata->customRepositoryClassName ?: (method_exists($documentManager->getConfiguration(), 'getDefaultDocumentRepositoryClassName') ? $documentManager->getConfiguration()->getDefaultDocumentRepositoryClassName() : $documentManager->getConfiguration()->getDefaultRepositoryClassName());

        return $this->managedRepositories[$repositoryHash] = new $repositoryClassName($documentManager, $documentManager->getUnitOfWork(), $metadata);
    }
}
