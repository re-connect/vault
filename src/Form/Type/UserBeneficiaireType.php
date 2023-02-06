<?php

namespace App\Form\Type;

use App\Entity\User;
use App\EventSubscriber\AddFormattedPhoneSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserBeneficiaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $regex = "^[a-zA-ZáàâäãåąçčćęéèêëėįíìîïłńñóòôöõøšúùûüųýÿżźžÁÀÂÄÃÅĄÇČĆĘÉÈÊËĖÍÌÎÏŁĮŃÑÓÒÔÖÕØŠÚÙÛÜŲÝŸŽ \-']+$";
        $builder
            ->add('prenom', null, [
                'required' => true,
                'label' => 'registerForm.prenom',
                'attr' => [
                    'pattern' => $regex,
                    'oninvalid' => 'this.setCustomValidity("Vous ne pouvez entrer que des lettres, tirets (-) ou apostrophes (\')")',
                    'oninput' => "this.setCustomValidity('')",
                ],
            ])
            ->add('nom', null, [
                'required' => true,
                'label' => 'registerForm.nom',
                'attr' => [
                    'pattern' => $regex,
                    'oninvalid' => 'this.setCustomValidity("Vous ne pouvez entrer que des lettres, tirets (-) ou apostrophes (\')")',
                    'oninput' => "this.setCustomValidity('')",
                ],
            ])
            ->add('telephone', null, [
                    'required' => false,
                    'label' => 'registerForm.telephone',
                    'attr' => [
                        'class' => 'intl-tel-input',
                    ],
                ]
            )
            ->addEventSubscriber(new AddFormattedPhoneSubscriber())
            ->add('email', EmailType::class, ['required' => false, 'label' => 'registerForm.email'])
            ->add('adresse', AdresseType::class, ['required' => false, 'label' => 'registerForm.adresse'])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'label' => 'registerForm.motDePasse',
                'options' => ['translation_domain' => 'FOSUserBundle'],
                'first_options' => ['label' => 'form.password'],
                'second_options' => ['label' => 'form.password_confirmation'],
                'invalid_message' => 'fos_user.password.mismatch',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'validation_groups' => ['beneficiaire', 'password'],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return $this->getName();
    }

    public function getName(): string
    {
        return 're_form_userbeneficiaire';
    }
}
