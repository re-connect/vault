<?php

namespace App\FormV2\UserAffiliation;

use App\FormV2\UserAffiliation\Model\SearchBeneficiaryFormModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchBeneficiaryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('lastname', TextType::class, [
                'required' => false,
                'label' => 'name',
            ])
            ->add('firstname', TextType::class, [
                'required' => false,
                'label' => 'firstname',
            ])
            ->add('birthDate', BirthdayType::class, [
                'required' => false,
                'label' => 'birthdate',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SearchBeneficiaryFormModel::class,
        ]);
    }
}
