<?php

namespace App\Form\Type;

use App\Entity\User;
use App\EventSubscriber\AddFormattedPhoneSubscriber;
use App\FormV2\Field\PasswordField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', null, ['label' => 'username'])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'password',
                'attr' => [
                    ...PasswordField::PASSWORD_STRENGTH_CONTROLLER_DATA_ATTRIBUTES,
                    'autocomplete' => 'new-password',
                ],
            ])
            ->add('prenom', null, ['label' => 'firstname'])
            ->add('nom', null, ['label' => 'name'])
            ->add('telephone', null, [
                'required' => false,
                'label' => 'telephone',
                'attr' => [
                    'class' => 'intl-tel-input',
                ],
            ])
            ->addEventSubscriber(new AddFormattedPhoneSubscriber())
            ->add('email', EmailType::class, ['required' => false, 'label' => 'email'])
            ->add('adresse', AdresseType::class, ['required' => false, 'label' => 'your_address'])
            ->add('submit', SubmitType::class, [
                'label' => 'confirm',
                'attr' => [
                    'class' => 'btn-blue',
                ],
            ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'validation_groups' => ['membre', 'password'],
        ]);
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return $this->getName();
    }

    public function getName(): string
    {
        return 're_form_user';
    }
}
