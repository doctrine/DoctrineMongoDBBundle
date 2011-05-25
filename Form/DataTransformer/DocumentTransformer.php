<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\DoctrineMongoDBBundle\Form\DataTransformer;

use Doctrine\ODM\MongoDB\DocumentRepository;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Transforms a document to an id string.
 *
 * @author Kris Wallsmith <kris@symfony.com>
 */
class DocumentTransformer implements DataTransformerInterface
{
    private $uow;
    private $repo;

    public function __construct(UnitOfWork $uow, DocumentRepository $repo)
    {
        $this->uow  = $uow;
        $this->repo = $repo;
    }

    public function transform($document)
    {
        if (null === $document || '' === $document) {
            return '';
        }

        return $this->uow->getDocumentIdentifier($document);
    }

    public function reverseTransform($id)
    {
        if (null === $id || '' === $id) {
            return;
        }

        if (!$document = $this->repo->find($id)) {
            throw new TransformationFailedException(sprintf('The document with id "%s" could not be found', $id));
        }

        return $document;
    }
}
