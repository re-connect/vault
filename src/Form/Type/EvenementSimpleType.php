<?php

namespace App\Form\Type;

use App\Entity\DonneePersonnelle;
use App\Entity\Evenement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EvenementSimpleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom', null, [
                'label' => 'evenement.nomLabel',
                'label_attr' => ['class' => 'font-size-1'],
            ])
            ->add(
                'date',
                DateTimeType::class,
                [
                    'label' => false,
                    'date_widget' => 'single_text',
                ]
            )
            ->add(
                'timezone',
                TimezoneType::class,
                [
                    'attr' => ['data-controller' => 'timezone'],
                    'row_attr' => ['class' => 'hidden'],
                ],
            )
            ->add(
                'lieu',
                null,
                [
                    'label' => 'evenement.lieuLabel',
                    'label_attr' => ['class' => 'font-size-1'],
                    'required' => false,
                ]
            )
            ->add(
                'commentaire',
                TextareaType::class,
                [
                    'label' => 'evenement.commentaireLabel',
                    'label_attr' => ['class' => 'font-size-1'],
                    'required' => false,
                ]
            )
            ->add('rappels', CollectionType::class, [
                'entry_type' => RappelType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'entry_options' => ['label' => false],
                'by_reference' => false,
                'required' => true,
                'label' => 'reminder_sms',
                'label_attr' => ['class' => 'font-size-1'],
            ])
            ->add(
                'bPrive',
                ChoiceType::class,
                [
                    'label' => 'access',
                    'label_attr' => ['class' => 'font-size-1'],
                    'expanded' => true,
                    'choices' => DonneePersonnelle::getArBPrive(),
                    'data' => DonneePersonnelle::PRIVE,
                ]
            )
            ->add('submit', SubmitType::class, ['label' => 'confirm', 'attr' => ['class' => 'btn-blue btn btn-green font-size-1 js-loading-container']])
            ->setAction('#')
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                $data = $event->getData();
                if (!$data instanceof Evenement || !$data->getDate() || !$data->getTimezone()) {
                    return;
                }

                $date = $data->getDate();
                $timezone = $data->getTimezone();

                $data->setDate(new \DateTime($date->format('Y-m-d H:i:s'), new \DateTimeZone($timezone)));
                $event->setData($data);
            });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Evenement::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return $this->getName();
    }

    public function getName(): string
    {
        return 're_form_evenementsimple';
    }
}
