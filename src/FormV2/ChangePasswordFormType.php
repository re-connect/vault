<?php

namespace App\FormV2;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Contracts\Translation\TranslatorInterface;

class ChangePasswordFormType extends AbstractType
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isBeneficiaire = $options['isBeneficiaire'];

        $constraintsBeneficiaire = [
                new NotBlank([
                    'message' => $this->translator->trans('you_must_choose_password'),
                ]),
                new Length([
                    'min' => 5,
                    'minMessage' => $this->translator->trans('password_too_short'),
                    // max length allowed by Symfony for security reasons
                    'max' => 4096,
                ]),
                new Regex([
                    'pattern' => '#^[\S]+$#',
                    'message' => $this->translator->trans('password_wrong_format'),
                ]),
            ];

        $constraintsMembre = [
            new Length([
                'min' => 8,
                'minMessage' => $this->translator->trans('password_too_short'),
                // max length allowed by Symfony for security reasons
                'max' => 4096,
            ]),
            new Callback([
                'callback' => ['App\Entity\User', 'validatePassword'],
            ]),
        ];
        $builder
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'constraints' => $isBeneficiaire ? $constraintsBeneficiaire : $constraintsMembre,
                    'label' => 'your_new_password',
                    'attr' => [
                        'autocomplete' => 'new-password',
                        'data-password-strength-target' => 'input',
                        'data-action' => 'password-strength#check',
                    ],
                ],
                'second_options' => [
                    'label' => 'confirm_new_password',
                ],
                'invalid_message' => $this->translator->trans('passwords_mismatch'),
                // Instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
            ]);

        if ($options['checkCurrentPassword']) {
            $builder
                ->add('currentPassword', PasswordType::class, [
                    'label' => 'actual_password',
                    'attr' => [
                        'autocomplete' => 'new-password',
                    ],
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'isBeneficiaire' => false,
            'checkCurrentPassword' => false,
        ])
        ->setAllowedTypes('isBeneficiaire', 'bool')
        ->setAllowedTypes('checkCurrentPassword', 'bool');
    }
}
