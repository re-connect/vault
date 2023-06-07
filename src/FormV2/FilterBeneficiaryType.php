<?php

namespace App\FormV2;

use App\Entity\Centre;
use App\ServiceV2\Traits\UserAwareTrait;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FilterBeneficiaryType extends AbstractType
{
    use UserAwareTrait;

    public function __construct(private Security $security)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('search', TextType::class, [
                'label' => false,
                'required' => false,
                'attr' => [
                    'placeholder' => 'main.chercher',
                    'autofocus' => true,
                ],
            ])->add('relay', EntityType::class, [
                'class' => Centre::class,
                'choice_label' => 'nom',
                'label' => false,
                'placeholder' => 'choose_relay',
                'required' => false,
                'choices' => $this->getUser()?->getAffiliatedRelaysWithBeneficiaryManagement()?->toArray(),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
