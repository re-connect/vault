<?php

namespace App\FormV2;

use App\Entity\Rappel;
use App\ListenerV2\TimezoneListener;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RappelType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', DateTimeType::class, [
                    'label' => false,
                    'date_widget' => 'single_text',
                    'minutes' => [0, 15, 30, 45],
                ]
            )
            ->add('timezone', TimezoneType::class, [
                    'attr' => ['data-controller' => 'timezone'],
                    'row_attr' => ['class' => 'd-none'],
                ]
            )->addEventSubscriber(new TimezoneListener());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Rappel::class,
        ]);
    }
}
