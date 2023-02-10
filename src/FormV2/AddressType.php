<?php

namespace App\FormV2;

use App\Entity\Adresse;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'main.adresse.rue',
            ])
            ->add('ville', TextType::class, [
                'label' => 'city',
            ])
            ->add('codePostal', TextType::class, [
                'label' => 'postal_code',
            ])
            ->add('pays', TextType::class, [
                'label' => 'country',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Adresse::class,
        ]);
    }
}
