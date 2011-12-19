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

namespace Doctrine\Bundle\MongoDBBundle\Form\DataTransformer;

use Doctrine\Bundle\MongoDBBundle\Form\ChoiceList\DocumentChoiceList;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

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
