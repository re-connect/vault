<?php

namespace App\FormV2;

use App\Entity\User;
use App\ServiceV2\Traits\UserAwareTrait;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MfaType extends AbstractType
{
    use UserAwareTrait;

    public function __construct(private readonly Security $security)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('mfaEnabled', CheckboxType::class, [
            'required' => false,
            'row_attr' => ['class' => 'col-12 mt-3'],
            'label' => 'enable_mfa',
        ])
            ->add('mfaMethod', ChoiceType::class, [
                'required' => false,
                'placeholder' => false,
                'label' => 'mfa_method',
                'choices' => array_combine(User::MFA_METHODS, User::MFA_METHODS),
                'expanded' => true,
                'multiple' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'validation_groups' => [$this->getUser()?->isBeneficiaire() ? 'beneficiaire' : 'membre'],
        ]);
    }
}
