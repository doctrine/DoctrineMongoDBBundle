<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Form\Type;

use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form\Guesser;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GuesserTestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            // Not setting "date_widget" is deprecated in Symfony 6.4
            ->add('date', null, ['date_widget' => 'single_text'])
            ->add('ts', null, ['date_widget' => 'single_text'])
            ->add('categories', null, ['document_manager' => $options['dm']])
            ->add('boolField')
            ->add('floatField')
            ->add('intField')
            ->add('collectionField')
            ->add('nonMappedField');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Guesser::class,
        ])->setRequired('dm');
    }

    public function getBlockPrefix(): string
    {
        return 'guesser_test';
    }

    public function getName(): string
    {
        return $this->getBlockPrefix();
    }
}
