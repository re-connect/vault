<?php

namespace App\FormV2\ResetPassword\BeneficiaryRequest;

use App\FormV2\ChangePasswordFormType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResetPasswordSmsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('smsCode', TextType::class, [
                'required' => true,
                'label' => 'beneficiary_reset_password_SMS_code',
            ])
            ->add('password', ChangePasswordFormType::class, [
                'isBeneficiaire' => true,
                'mapped' => false,
                'label' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ResetPasswordSmsFormModel::class,
        ]);
    }
}
