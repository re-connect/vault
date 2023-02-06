<?php

namespace App\Form\Type;

use App\Entity\Centre;
use App\Entity\MembreCentre;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MembreCentreType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('centre', EntityType::class, ['class' => Centre::class, 'choices' => $options['centres']])
            ->add('droits', ChoiceType::class, [
                'required' => false,
                'multiple' => true,
                'expanded' => true,
                'choices' => [
                    'membre.droits.gestionBeneficiaires' => MembreCentre::TYPEDROIT_GESTION_BENEFICIAIRES,
                    'membre.droits.gestionMembres' => MembreCentre::TYPEDROIT_GESTION_MEMBRES,
                ],
            ]);
        $builder->get('droits')
            ->addModelTransformer(new CallbackTransformer(
                function ($droits) {
                    if (null === $droits) {
                        return null;
                    }
                    $normalizedDroits = [];
                    foreach ($droits as $droit) {
                        if ($droit) {
                            $normalizedDroits[] = $droit;
                        }
                    }

                    return $normalizedDroits;
                },
                function ($droits) {
                    return array_map(function () {
                        return true;
                    }, array_flip($droits));
                },
            ));
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['centres'] = $options['centres'];
        parent::buildView($view, $form, $options);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $optionsNormalizer = function (Options $options, $value) {
            $value['block_name'] = 'entry';

            return $value;
        };

        $resolver->setDefaults([
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
            'prototype' => true,
            'prototype_name' => '__name__',
            'type' => 'text',
            'options' => [],
            'delete_empty' => false,
            'centres' => [],
        ]);

        foreach ($optionsNormalizer as $option => $normalizer) {
            $resolver->setNormalizer('options', $optionsNormalizer);
        }
    }

    public function getBlockPrefix(): string
    {
        return $this->getName();
    }

    public function getName(): string
    {
        return 're_form_membrecentre';
    }
}
