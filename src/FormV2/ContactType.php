<?php

namespace App\FormV2;

use App\Entity\Contact;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'contact.nomLabel',
            ])
            ->add('prenom', TextType::class, [
                'label' => 'contact.prenomLabel',
            ])
            ->add('telephone', TextType::class, [
                'label' => 'contact.telephoneLabel',
                'required' => false,
            ])
            ->add('email', TextType::class, [
                'required' => false,
            ])
            ->add('commentaire', TextareaType::class, [
                'label' => 'contact.commentaireLabel',
                'required' => false,
            ])
            ->add('association', TextType::class, [
                'label' => 'contact.associationLabel',
                'required' => false,
            ])
            ->add('bPrive', ChoiceType::class, [
                'label' => 'access',
                'multiple' => false,
                'expanded' => true,
                'choices' => [
                    'private' => true,
                    'shared' => false,
                ],
                'data' => $options['private'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contact::class,
            'private' => true,
        ]);
    }
}
