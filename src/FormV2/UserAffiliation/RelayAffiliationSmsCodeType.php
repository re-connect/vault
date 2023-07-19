<?php

namespace App\FormV2\UserAffiliation;

use App\Entity\Beneficiaire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\EqualTo;

class RelayAffiliationSmsCodeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Beneficiaire $beneficiary */
        $beneficiary = $builder->getData();
        if ($beneficiary->getRelayInvitationSmsCode()) {
            $builder
                ->add('code', TextType::class, [
                    'label' => false,
                    'mapped' => false,
                    'constraints' => new EqualTo($beneficiary->getRelayInvitationSmsCode(), null, 'wrong_sms_code'),
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('data_class', Beneficiaire::class);
    }
}
