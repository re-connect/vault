<?php

namespace App\FormV2;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FirstMemberVisitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('sharePhone', CheckboxType::class, [
                'label' => 'membre.partageContact.ajouterNuméro',
            ])
            ->add('shareMail', CheckboxType::class, [
                'label' => 'membre.partageContact.ajouterMail',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
