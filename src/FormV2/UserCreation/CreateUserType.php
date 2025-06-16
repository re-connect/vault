<?php

namespace App\FormV2\UserCreation;

use App\Entity\Attributes\User;
use App\EventSubscriber\AddFormattedPhoneSubscriber;
use App\FormV2\Field\PasswordField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CreateUserType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('prenom', null, [
                'label' => 'firstname',
                'row_attr' => ['class' => 'col-6 mt-3 required'],
            ])
            ->add('nom', null, [
                'label' => 'name',
                'row_attr' => ['class' => 'col-6 mt-3 required'],
            ])
            ->add('telephone', null, [
                'required' => false,
                'label' => 'phone',
                'row_attr' => [
                    'class' => 'col-6 mt-3',
                    'data-controller' => 'intl-tel-input',
                ],
                'attr' => [
                    'data-intl-tel-input-target' => 'input',
                    'autocomplete' => 'tel',
                ],
            ])
            ->addEventSubscriber(new AddFormattedPhoneSubscriber())
            ->add('email', EmailType::class, [
                'required' => false,
                'row_attr' => ['class' => 'col-6 mt-3'],
                'label' => 'email',
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'password',
                    'attr' => [
                        ...PasswordField::PASSWORD_STRENGTH_CONTROLLER_DATA_ATTRIBUTES,
                        'autocomplete' => 'new-password',
                    ],
                    'row_attr' => ['class' => 'col-6'],
                ],
                'second_options' => [
                    'label' => 'password_confirm',
                    'row_attr' => ['class' => 'col-6'],
                ],
            ])
            ->add('mfaEnabled', CheckboxType::class, [
                'required' => false,
                'row_attr' => ['class' => 'col-12 mt-3'],
                'label' => 'enable_mfa',
                'help' => 'enable_mfa_help_pro_creation',
            ])
            ->add('mfaMethod', ChoiceType::class, [
                'required' => false,
                'placeholder' => false,
                'label' => 'mfa_method',
                'choices' => array_combine(User::MFA_METHODS, User::MFA_METHODS),
                'expanded' => true,
                'multiple' => false,
            ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'validation_groups' => function (FormInterface $form) {
                /** @var User $data */
                $data = $form->getData();
                if ($data->isBeneficiaire()) {
                    return ['password', 'beneficiaire'];
                } elseif ($data->isMembre()) {
                    return ['password', 'membre'];
                }

                return [];
            },
        ]);
    }
}
