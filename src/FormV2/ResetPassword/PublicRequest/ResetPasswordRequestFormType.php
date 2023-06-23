<?php

namespace App\FormV2\ResetPassword\PublicRequest;

use App\EventSubscriber\AddFormattedPhoneSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ResetPasswordRequestFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['sms']) {
            $builder
                ->add('phone', TelType::class, [
                    'label' => 'resetting.public.textPhone',
                    'label_attr' => [
                        'class' => 'w-75 mt-1 mb-3',
                    ],
                    'attr' => [
                        'class' => 'mb-4 intl-tel-input',
                    ],
                    'row_attr' => [
                        'class' => 'w-50 mx-auto',
                    ],
                ])
                ->addEventSubscriber(new AddFormattedPhoneSubscriber());
        } else {
            $builder
                ->add('email', EmailType::class, [
                    'constraints' => [
                        new NotBlank([
                            'message' => 'Please enter your email',
                        ]),
                    ],
                    'label' => 'resetting.public.text',
                    'label_attr' => [
                        'class' => 'w-75 mt-1 mb-3',
                    ],
                    'attr' => [
                        'class' => 'w-50 mx-auto mb-2',
                        'placeholder' => 'email',
                    ],
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ResetPasswordRequestFormModel::class,
            'sms' => false,
        ]);
    }
}
