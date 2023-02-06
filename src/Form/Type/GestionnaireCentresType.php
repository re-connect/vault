<?php

namespace App\Form\Type;

use App\Entity\Gestionnaire;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class GestionnaireCentresType extends AbstractType
{
    private TranslatorInterface $translator;
    private EntityManagerInterface $em;

    /**
     * Constructor.
     */
    public function __construct(EntityManagerInterface $em, TranslatorInterface $translator)
    {
        $this->em = $em;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('centres', CollectionType::class, [
                'type' => new CentreType($this->em, $this->translator),
                'by_reference' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
            ])
            ->add('submit', SubmitType::class, ['label' => 'main.etapeSuivante', 'attr' => ['class' => 'btn-grey']]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Gestionnaire::class,
            'validation_groups' => ['centre'],
        ]);
    }

    public function getName(): string
    {
        return 're_form_gestionnaire_centres';
    }
}
