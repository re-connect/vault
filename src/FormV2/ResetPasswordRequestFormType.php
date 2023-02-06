<?php

namespace App\FormV2;

use App\EventSubscriber\AddFormattedPhoneSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
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
                ->add('telephone', TelType::class, [
                    'label' => 'resetting.public.textPhone',
                    'label_attr' => [
                        'class' => 'w-75 text-primary mt-1 mb-3',
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
                        'class' => 'w-75 text-primary mt-1 mb-3',
                    ],
                    'attr' => [
                        'class' => 'w-50 mx-auto text-primary mb-2',
                        'placeholder' => 'email',
                    ],
                ]);
        }
        $builder
            ->add('submitFormReset', SubmitType::class, [
                'label' => 'confirm',
                'attr' => ['class' => 'btn btn-primary mt-3'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['sms' => false]);
    }
}
