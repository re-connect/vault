<?php

namespace App\Form\Type;

use App\Entity\Document;
use App\Entity\DonneePersonnelle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DocumentSimpleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom', TextType::class, ['label' => 'name'])
            ->add('bPrive', ChoiceType::class, [
                'label' => 'donneePersonnelle.form.access.label',
                'required' => true,
                'expanded' => true,
                'choices' => DonneePersonnelle::getArBPrive(),
            ])
            ->add('submit', SubmitType::class, ['label' => 'confirm'])
            ->add('cancel', SubmitType::class, ['label' => 'main.annuler'])
            ->setAction('#');
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Document::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return $this->getName();
    }

    public function getName(): string
    {
        return 're_form_documentsimple';
    }
}
