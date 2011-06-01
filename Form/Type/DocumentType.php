<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\DoctrineMongoDBBundle\Form\Type;

use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Bundle\DoctrineMongoDBBundle\Form\ChoiceList\DocumentChoiceList;
use Symfony\Bundle\DoctrineMongoDBBundle\Form\EventListener\MergeCollectionListener;
use Symfony\Bundle\DoctrineMongoDBBundle\Form\DataTransformer\DocumentsToArrayTransformer;
use Symfony\Bundle\DoctrineMongoDBBundle\Form\DataTransformer\DocumentToIdTransformer;
use Symfony\Component\Form\AbstractType;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Form type for a MongoDB document
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 */
class DocumentType extends AbstractType
{
    private $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    public function buildForm(FormBuilder $builder, array $options)
    {
        if ($options['multiple']) {
            $builder->addEventSubscriber(new MergeCollectionListener())
                ->prependClientTransformer(new DocumentsToArrayTransformer($options['choice_list']));
        } else {
            $builder->prependClientTransformer(new DocumentToIdTransformer($options['choice_list']));
        }
    }

    public function getDefaultOptions(array $options)
    {
        $defaultOptions = array(
            'choices'           => array(),
            'class'             => null,
            'document_manager'  => $this->documentManager,
            'expanded'          => false,
            'multiple'          => false,
            'preferred_choices' => array(),
            'property'          => null,
            'query_builder'     => null,
            'template'          => 'choice',
        );

        $options = array_replace($defaultOptions, $options);

        if (!isset($options['choice_list'])) {
            $defaultOptions['choice_list'] = new DocumentChoiceList(
                $options['document_manager'],
                $options['class'],
                $options['property'],
                $options['query_builder'],
                $options['choices']
            );
        }

        return $defaultOptions;
    }

    public function getParent(array $options)
    {
        return 'choice';
    }

    public function getName()
    {
        return 'document';
    }
}
