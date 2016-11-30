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
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bridge\Doctrine\Form\Type\DoctrineType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'document_manager' => null,
        ]);

        $registry = $this->registry;
        $normalizer = function (Options $options, $manager) use ($registry) {
            if (isset($options['document_manager']) && $manager) {
                throw new \InvalidArgumentException('You cannot set both an "em" and "document_manager" option.');
            }

            $manager = $options['document_manager'] ?: $manager;

            if (null === $manager) {
                return $registry->getManagerForClass($options['class']);
            }

            if ($manager instanceof ObjectManager) {
                return $manager;
            }

            return $registry->getManager($manager);
        };

        $resolver->setNormalizer('em', $normalizer);

        $resolver->setAllowedTypes('document_manager', ['null', 'string', DocumentManager::class]);
    }

    /**
     * @inheritdoc
     *
     * @internal Symfony 2.8 compatibility
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'document';
    }

    /**
     * @inheritdoc
     *
     * @internal Symfony 2.7 compatibility
     *
     * @return string
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }
}
