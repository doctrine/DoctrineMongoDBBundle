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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Transforms a collection of documents into an array of id strings.
 *
 * @author Kris Wallsmith <kris@symfony.com>
 */
class CollectionTransformer extends DocumentTransformer
{
    private $collection;

    public function __construct(ClassMetadataInfo $meta, DocumentRepository $repo, Collection $collection = null)
    {
        parent::__construct($meta, $repo);

        $this->collection = $collection;
    }

    public function transform($collection)
    {
        $ids = array();
        foreach ($collection as $document) {
            $ids[] = parent::transform($document);
        }

        return $ids;
    }

    public function reverseTransform($ids)
    {
        $collection = $this->collection ?: new ArrayCollection();

        $old = $collection->toArray();
        $new = array();
        $notFound = array();

        foreach ($ids as $id) {
            try {
                $new[] = parent::reverseTransform($id);
            } catch (TransformationFailedException $e) {
                $notFound[] = $id;
            }
        }

        if (0 < count($notFound)) {
            throw new TransformationFailedException(sprintf('The documents with ids "%s" could not be found', implode('", "', $notFound)));
        }

        // add added documents
        foreach ($new as $document) {
            if (!$collection->contains($document)) {
                $collection->add($document);
            }
        }

        // remove removed documents
        foreach ($old as $document) {
            if (!in_array($document, $new, true)) {
                $collection->remove($document);
            }
        }

        return $collection;
    }
}
