<?php

namespace App\FormV2\FilterUser;

use App\Entity\Centre;
use App\ServiceV2\Traits\UserAwareTrait;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FilterUserType extends AbstractType
{
    use UserAwareTrait;

    public function __construct(private readonly Security $security)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('search', TextType::class, [
                'label' => false,
                'required' => false,
                'row_attr' => ['class' => 'input-with-glass'],
                'attr' => [
                    'placeholder' => 'main.chercher',
                    'autofocus' => true,
                    'data-ajax-list-filter-target' => 'input',
                    'data-input-name' => 'search',
                    'data-action' => 'ajax-list-filter#filter',
                ],
            ])->add('relay', EntityType::class, [
                'class' => Centre::class,
                'choice_label' => 'nom',
                'label' => false,
                'placeholder' => 'choose_relay',
                'required' => false,
                'choices' => $options['relays'],
                'attr' => [
                    'data-ajax-list-filter-target' => 'input',
                    'data-input-name' => 'relay',
                    'data-action' => 'ajax-list-filter#filter',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'relays' => null,
        ]);
    }
}
