<?php

namespace App\FormV2;

use App\FormV2\Field\PasswordField;
use App\Validator\Constraints\PasswordCriteria;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class ChangePasswordFormType extends AbstractType
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'constraints' => [new PasswordCriteria($options['user'])],
                    'label' => 'your_new_password',
                    'attr' => [
                        ...PasswordField::PASSWORD_STRENGTH_CONTROLLER_DATA_ATTRIBUTES,
                        'autocomplete' => 'new-password',
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

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'checkCurrentPassword' => false,
            'user' => null,
        ])
        ->setAllowedTypes('checkCurrentPassword', 'bool');
    }
}
