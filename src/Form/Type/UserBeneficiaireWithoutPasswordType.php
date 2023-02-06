<?php

namespace App\Form\Type;

use App\Entity\User;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserBeneficiaireWithoutPasswordType extends UserBeneficiaireType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        $builder
            ->remove('plainPassword');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'validation_groups' => ['beneficiaire'],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return $this->getName();
    }

    public function getName(): string
    {
        return 're_form_userbeneficiairewithoutpassword';
    }
}
