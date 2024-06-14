<?php

namespace App\Form\Type;

use App\Entity\Centre;
use App\Entity\Region;
use App\Form\Model\ExportModel;
use App\Repository\CentreRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExportType extends AbstractType
{
    #[\Override]
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
                'query_builder' => fn (CentreRepository $repository) => $repository->createQueryBuilder('c')
                    ->orderBy('c.nom', 'ASC'),
            ])
            ->add('regions', EntityType::class, [
                'class' => Region::class,
                'label' => 'regions',
                'required' => false,
                'multiple' => true,
                'attr' => [
                    'class' => 'has-select2',
                ],
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

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ExportModel::class,
        ]);
    }
}
