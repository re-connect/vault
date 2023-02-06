<?php

namespace App\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class EvenementType extends EvenementSimpleType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->remove('bPrive')
            ->add('submit', SubmitType::class, ['label' => 'confirm', 'attr' => ['class' => 'btn btn-green btn-blue font-size-1 js-loading-container']]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 're_form_evenement';
    }
}
