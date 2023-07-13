<?php

namespace App\Form\Type;

use App\Entity\Gestionnaire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GestionnaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('user', UserGestionnaireType::class, [
                'label' => false,
            ])
            ->add('association', AssociationType::class, ['label' => false]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $validation_groups = ['gestionnaire'];

        $resolver->setDefaults([
            'allow_extra_fields' => true,
            'data_class' => Gestionnaire::class,
            'validation_groups' => $validation_groups,
            'gestionnaire' => null,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return $this->getName();
    }

    public function getName(): string
    {
        return 're_form_gestionnaire';
    }
}
