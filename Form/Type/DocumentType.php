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

namespace Doctrine\Bundle\MongoDBBundle\Form\Type;

use Doctrine\Bundle\MongoDBBundle\Form\ChoiceList\MongoDBQueryBuilderLoader;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bridge\Doctrine\Form\Type\DoctrineType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Form type for a MongoDB document
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 * @author Christophe Coevoet <stof@notk.org>
 */
class DocumentType extends DoctrineType
{
    /**
     * @see Symfony\Bridge\Doctrine\Form\Type\DoctrineType::getLoader()
     */
    public function getLoader(ObjectManager $manager, $queryBuilder, $class)
    {
        return new MongoDBQueryBuilderLoader(
            $queryBuilder,
            $manager,
            $class
        );
    }

    /**
     * @param OptionsResolver $resolver
     * @see Symfony\Bridge\Doctrine\Form\Type\DoctrineType::configureOptions()
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        if (method_exists('Symfony\Component\Form\AbstractType', 'configureOptions') && $resolver instanceof OptionsResolver) {
            parent::configureOptions($resolver);
        } else {
            parent::setDefaultOptions($resolver);
        }

        $resolver->setDefaults(array(
            'document_manager' => null,
        ));

        $registry = $this->registry;
        $normalizer = function (Options $options, $manager) use ($registry) {
            if (isset($options['document_manager']) && $manager) {
                throw new \InvalidArgumentException('You cannot set both an "em" and "document_manager" option.');
            }

            $manager = $options['document_manager'] ?: $manager;

            if (null === $manager) {
                return $registry->getManagerForClass($options['class']);
            }

            return $registry->getManager($manager);
        };

        if (method_exists($resolver, 'setNormalizer')) {
            $resolver->setNormalizer('em', $normalizer);
        } else {
            $resolver->setNormalizers(array('em' => $normalizer));
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     * @deprecated This method is deprecated. Use Doctrine\Bundle\MongoDBBundle\Form\Type::configureOptions() instead.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $this->configureOptions($resolver);
    }

    /**
     * @see Symfony\Component\Form\FormTypeInterface::getName()
     */
    public function getName()
    {
        return 'document';
    }
}
