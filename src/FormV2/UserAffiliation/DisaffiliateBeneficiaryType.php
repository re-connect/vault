<?php

namespace App\FormV2\UserAffiliation;

use App\Entity\Centre;
use App\FormV2\UserAffiliation\Model\DisaffiliateBeneficiaryFormModel;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DisaffiliateBeneficiaryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('relays', EntityType::class, [
                'multiple' => true,
                'expanded' => true,
                'choices' => $options['data']->getRelays(),
                'class' => Centre::class,
                'label' => false,
                'data' => null,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DisaffiliateBeneficiaryFormModel::class,
        ]);
    }
}
