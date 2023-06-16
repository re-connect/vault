<?php

namespace App\Form\Type;

use App\Entity\Contact;
use App\Entity\DonneePersonnelle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactSimpleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom', null, ['label' => 'name', 'label_attr' => ['class' => 'font-size-1']])
            ->add('prenom', null, ['label' => 'firstname', 'label_attr' => ['class' => 'font-size-1']])
            ->add('telephone', null, ['label' => 'contact.telephoneLabel', 'required' => false, 'label_attr' => ['class' => 'font-size-1']])
            ->add('email', EmailType::class, ['label' => 'email', 'required' => false, 'label_attr' => ['class' => 'font-size-1']])
            ->add('commentaire', TextareaType::class, ['label' => 'contact.commentaireLabel', 'required' => false, 'label_attr' => ['class' => 'font-size-1']])
            ->add('association', null, ['label' => 'contact.associationLabel', 'required' => false, 'label_attr' => ['class' => 'font-size-1']])
            ->add('bPrive', ChoiceType::class, [
                'label' => 'donneePersonnelle.form.access.label',
                'label_attr' => ['class' => 'font-size-1'],
                'required' => true,
                'expanded' => true,
                'choices' => DonneePersonnelle::getArBPrive(),
                'data' => DonneePersonnelle::PRIVE,
            ])
            ->add('submit', SubmitType::class, ['label' => 'confirm', 'attr' => ['class' => 'btn btn-blue btn-green font-size-1 js-loading-container']]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Contact::class,
            'validation_groups' => ['telephone'],
        ]);
    }

    public function getName(): string
    {
        return $this->getBlockPrefix();
    }

    public function getBlockPrefix(): string
    {
        return 're_form_contactsimple';
    }
}
