<?php

namespace App\FormV2\UserCreation;

use App\Entity\User;
use App\EventSubscriber\AddFormattedPhoneSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CreateUserType extends AbstractType
{
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
                        'autocomplete' => 'new-password',
                        'data-password-strength-target' => 'input',
                        'data-action' => 'password-strength#check',
                    ],
                    'row_attr' => ['class' => 'col-6 mt-3'],
                ],
                'second_options' => [
                    'label' => 'password_confirm',
                    'row_attr' => ['class' => 'col-6 mt-3'],
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'validation_groups' => function (FormInterface $form) {
                /** @var User $data */
                $data = $form->getData();
                if ($data->isBeneficiaire()) {
                    return ['password', 'password-beneficiaire', 'beneficiaire'];
                } elseif ($data->isMembre()) {
                    return ['password', 'password-membre', 'membre'];
                }

                return [];
            },
        ]);
    }
}
