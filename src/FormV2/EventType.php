<?php

namespace App\FormV2;

use App\Entity\DonneePersonnelle;
use App\Entity\Evenement;
use App\ListenerV2\TimezoneListener;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'evenement.nomLabel',
            ])
            ->add('date', DateTimeType::class, [
                    'label' => 'evenement.dateLabel',
                    'widget' => 'single_text',
                    'minutes' => [0, 15, 30, 45],
                ]
            )
            ->add('timezone', TimezoneType::class, [
                    'attr' => ['data-controller' => 'timezone'],
                    'row_attr' => ['class' => 'd-none'],
                ],
            )
            ->add('lieu', TextType::class, [
                    'label' => 'evenement.lieuLabel',
                    'required' => false,
                ]
            )
            ->add('commentaire', TextareaType::class, [
                    'label' => 'evenement.commentaireLabel',
                    'required' => false,
                ]
            )
            ->add('rappels', LiveCollectionType::class, [
                'entry_type' => RappelType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'required' => true,
                'label' => 'reminder_sms',
            ])
            ->add('bPrive', ChoiceType::class, [
                    'label' => 'access',
                    'expanded' => true,
                    'choices' => DonneePersonnelle::getArBPrive(),
                    'data' => $options['private'],
                ]
            )
            ->addEventSubscriber(new TimezoneListener());
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Evenement::class,
            'private' => true,
        ]);
    }
}
