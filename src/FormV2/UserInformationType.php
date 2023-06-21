<?php

namespace App\FormV2;

use App\Entity\User;
use App\EventSubscriber\AddFormattedPhoneSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserInformationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('prenom', null, [
                'label' => 'firstname',
                'row_attr' => ['class' => 'col-6 mt-3'],
            ])
            ->add('nom', null, [
                'label' => 'name',
                'row_attr' => ['class' => 'col-6 mt-3'],
            ])
            ->add('telephone', null, [
                'required' => false,
                'label' => 'phone',
                'row_attr' => ['class' => 'col-6 mt-3'],
                'attr' => [
                    'class' => 'intl-tel-input',
                ],
            ])
            ->addEventSubscriber(new AddFormattedPhoneSubscriber())
            ->add('email', EmailType::class, [
                'required' => false,
                'row_attr' => ['class' => 'col-6 mt-3'],
                'label' => 'email',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'validation_groups' => ['beneficiaire', 'membre'],
        ]);
    }
}
