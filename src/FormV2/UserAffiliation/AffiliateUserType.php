<?php

namespace App\FormV2\UserAffiliation;

use App\Entity\Centre;
use App\FormV2\UserAffiliation\Model\AffiliateUserModel;
use App\ServiceV2\Traits\UserAwareTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AffiliateUserType extends AbstractType
{
    use UserAwareTrait;

    public function __construct(private readonly Security $security)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('relays', EntityType::class, [
                'multiple' => true,
                'expanded' => true,
                'choices' => $options['available_relays'],
                'class' => Centre::class,
                'label' => false,
                'choice_label' => 'nameAndAddress',
                'row_attr' => ['class' => 'relay-checkboxes'],
                'label_attr' => ['class' => 'btn btn-outline-primary no-hover'],
                'choice_attr' => fn () => ['class' => 'btn-check'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AffiliateUserModel::class,
            'available_relays' => new ArrayCollection(),
        ]);
    }
}
