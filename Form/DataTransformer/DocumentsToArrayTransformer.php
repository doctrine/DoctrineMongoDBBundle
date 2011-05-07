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

use Symfony\Bundle\DoctrineMongoDBBundle\Form\ChoiceList\DocumentChoiceList;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\DataTransformerInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Transforms collections into arrays of ids
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 */
class DocumentsToArrayTransformer implements DataTransformerInterface
{
    private $choiceList;

    public function __construct(DocumentChoiceList $choiceList)
    {
        $this->choiceList = $choiceList;
    }

    /**
     * Transforms documents into choice keys
     *
     * @param Collection|object $collection A collection of documents, a single document or
     *                                      NULL
     * @return mixed An array of choice keys, a single key or NULL
     */
    public function transform($collection)
    {
        if (null === $collection) {
            return array();
        }

        if (!($collection instanceof Collection)) {
            throw new UnexpectedTypeException($collection, 'Doctrine\Common\Collection\Collection');
        }

        $array = array();

        foreach ($collection as $document) {
            $array[] = $this->choiceList->getIdentifierValue($document);
        }

        return $array;
    }

    /**
     * Transforms choice keys into documents
     *
     * @param  mixed $keys   An array of keys, a single key or NULL
     * @return Collection|object  A collection of documents, a single document
     *                            or NULL
     */
    public function reverseTransform($keys)
    {
        $collection = new ArrayCollection();

        if ('' === $keys || null === $keys) {
            return $collection;
        }

        if (!is_array($keys)) {
            throw new UnexpectedTypeException($keys, 'array');
        }

        $notFound = array();

        // optimize this into a SELECT WHERE IN query
        foreach ($keys as $key) {
            if ($document = $this->choiceList->getDocument($key)) {
                $collection->add($document);
            } else {
                $notFound[] = $key;
            }
        }

        if (count($notFound) > 0) {
            throw new TransformationFailedException(sprintf('The documents with keys "%s" could not be found', implode('", "', $notFound)));
        }

        return $collection;
    }
}
