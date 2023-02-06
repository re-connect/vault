<?php

namespace App\Form\Type;

use App\Entity\Rappel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RappelType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'date',
                DateTimeType::class,
                [
                    'label' => false,
                    'date_widget' => 'single_text',
                    'minutes' => [0, 15, 30, 45],
                ]
            )
            ->add(
                'timezone',
                TimezoneType::class,
                [
                    'attr' => ['data-controller' => 'timezone'],
                    'row_attr' => ['class' => 'hidden'],
                ]
            )->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                $data = $event->getData();
                if (!$data instanceof Rappel || !$data->getDate() || !$data->getTimezone()) {
                    return;
                }

                $date = $data->getDate();
                $timezone = $data->getTimezone();

                $data->setDate(new \DateTime($date->format('Y-m-d H:i:s'), new \DateTimeZone($timezone)));
                $event->setData($data);
            });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Rappel::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 're_appbundle_rappel';
    }
}
