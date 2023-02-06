<?php

namespace App\Form\Type;

use App\Entity\Association;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AssociationType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('nom', null, ['label' => 'devenirUnRelaiReconnect.accueil.associationLbl']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Association::class,
            'validation_groups' => ['association'],
        ]);
    }

    public function getName(): string
    {
        return 're_form_association';
    }
}
