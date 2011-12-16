<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Bundle\DoctrineMongoDBBundle\Form\Type;

use Doctrine\Bundle\DoctrineMongoDBBundle\Form\ChoiceList\DocumentChoiceList;
use Doctrine\Bundle\DoctrineMongoDBBundle\Form\DataTransformer\DocumentsToArrayTransformer;
use Doctrine\Bundle\DoctrineMongoDBBundle\Form\DataTransformer\DocumentToIdTransformer;
use Doctrine\Bundle\DoctrineMongoDBBundle\Form\EventListener\MergeCollectionListener;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormFactoryInterface;

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
}
