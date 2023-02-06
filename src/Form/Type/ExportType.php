<?php

namespace App\Form\Type;

use App\Entity\Centre;
use App\Form\Model\ExportModel;
use App\Repository\CentreRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('centers', EntityType::class, [
                'label' => 'centers',
                'required' => false,
                'class' => Centre::class,
                'choice_label' => 'nom',
                'multiple' => true,
                'attr' => [
                    'class' => 'has-select2',
                ],
                'query_builder' => function (CentreRepository $repository) {
                    return $repository->createQueryBuilder('c')
                        ->orderBy('c.nom', 'ASC');
                },
            ])
            ->add('regions', ChoiceType::class, [
                'label' => 'regions',
                'required' => false,
                'multiple' => true,
                'attr' => [
                    'class' => 'has-select2',
                ],
                'choices' => array_combine(Centre::REGIONS, Centre::REGIONS),
            ])
            ->add('start_date', DateType::class, [
                'label' => 'date_period_start',
                'widget' => 'single_text',
                'attr' => ['class' => 'text-center'],
            ])
            ->add('end_date', DateType::class, [
                'label' => 'date_period_end',
                'widget' => 'single_text',
                'attr' => ['class' => 'text-center'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ExportModel::class,
        ]);
    }
}
