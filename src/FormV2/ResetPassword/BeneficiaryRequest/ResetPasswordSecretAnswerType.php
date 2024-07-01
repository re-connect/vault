<?php

namespace App\FormV2\ResetPassword\BeneficiaryRequest;

use App\FormV2\ChangePasswordFormType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResetPasswordSecretAnswerType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('secretAnswer', TextType::class, [
                'required' => true,
                'label' => 'secret_answer',
            ])
            ->add('password', ChangePasswordFormType::class, [
                'mapped' => false,
                'label' => false,
            ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ResetPasswordSecretAnswerFormModel::class,
        ]);
    }
}
