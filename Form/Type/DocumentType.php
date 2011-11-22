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

use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Bundle\DoctrineMongoDBBundle\Form\ChoiceList\DocumentChoiceList;
use Symfony\Bundle\DoctrineMongoDBBundle\Form\EventListener\MergeCollectionListener;
use Symfony\Bundle\DoctrineMongoDBBundle\Form\DataTransformer\DocumentsToArrayTransformer;
use Symfony\Bundle\DoctrineMongoDBBundle\Form\DataTransformer\DocumentToIdTransformer;
use Symfony\Component\Form\AbstractType;

/**
 * Form type for a MongoDB document
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 */
class DocumentType extends AbstractType
{
    private $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function buildForm(FormBuilder $builder, array $options)
    {
        if ($options['multiple']) {
            $transformer = new DocumentsToArrayTransformer($options['choice_list']);
        } else {
            $transformer = new DocumentToIdTransformer($options['choice_list']);
        }

        if ($this->prependClientTransformer($builder, $transformer) && $options['multiple']) {
            $builder->addEventSubscriber(new MergeCollectionListener());
        }
    }

    public function getDefaultOptions(array $options)
    {
        $defaultOptions = array(
            'choices'           => array(),
            'class'             => null,
            'document_manager'  => null,
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
                $this->registry->getManager($options['document_manager']),
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

    /**
     * Checking for client transformer was added before. If so - returns false.
     *
     * @param \Symfony\Component\Form\FormBuilder $builder
     * @param \Symfony\Component\Form\DataTransformerInterface $prependedTransformer
     * @return bool
     */
    protected function prependClientTransformer(FormBuilder $builder, DataTransformerInterface $prependedTransformer)
    {
        $hasAlready = false;
        $transformers = $builder->getClientTransformers();
        foreach ($transformers as $transformer) {
            if ($prependedTransformer == $transformer) {
                $hasAlready = true;
            }
        }

        if (!$hasAlready) {
            $builder->prependClientTransformer($prependedTransformer);
        } else {
            unset($prependedTransformer);
        }

        return !$hasAlready;
    }
}
