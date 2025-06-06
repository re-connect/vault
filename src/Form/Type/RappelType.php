<?php

namespace App\Form\Type;

use App\Entity\Attributes\Rappel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RappelType extends AbstractType
{
    #[\Override]
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
            );
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Rappel::class,
        ]);
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 're_appbundle_rappel';
    }
}
