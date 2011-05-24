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
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Transforms documents to ids
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 */
class DocumentToIdTransformer implements DataTransformerInterface
{
    private $choiceList;

    public function __construct(DocumentChoiceList $choiceList)
    {
        $this->choiceList = $choiceList;
    }

    /**
     * Transforms documents into choice keys
     *
     * @param Collection|object $document A collection of documents, a single document or
     *                                  NULL
     * @return mixed An array of choice keys, a single key or NULL
     */
    public function transform($document)
    {
        if (null === $document || '' === $document) {
            return '';
        }

        if (!is_object($document)) {
            throw new UnexpectedTypeException($document, 'object');
        }

        return $this->choiceList->getIdentifierValue($document);
    }

    /**
     * Transforms choice keys into documents
     *
     * @param  mixed $key   An array of keys, a single key or NULL
     * @return Collection|object  A collection of documents, a single document
     *                            or NULL
     */
    public function reverseTransform($key)
    {
        if ('' === $key || null === $key) {
            return null;
        }

        if (!($document = $this->choiceList->getDocument($key))) {
            throw new TransformationFailedException(sprintf('The document with key "%s" could not be found', $key));
        }

        return $document;
    }
}
