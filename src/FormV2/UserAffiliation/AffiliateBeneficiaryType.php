<?php

namespace App\FormV2\UserAffiliation;

use App\Entity\Centre;
use App\FormV2\UserAffiliation\Model\AffiliateBeneficiaryFormModel;
use App\ServiceV2\Traits\UserAwareTrait;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class AffiliateBeneficiaryType extends AbstractType
{
    use UserAwareTrait;

    public function __construct(private Security $security)
    {
    }

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
                'choice_label' => 'nameAndAddress',
                'row_attr' => ['class' => 'relay-checkboxes'],
                'label_attr' => ['class' => 'btn btn-outline-primary no-hover'],
                'choice_attr' => fn () => ['class' => 'btn-check'],
            ])
            ->add('secretAnswer', TextType::class, [
                'label' => $options['beneficiary']?->getQuestionSecrete() ?? false,
                'required' => false,
                'attr' => [
                    'placeholder' => 'secret_answer_optional',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AffiliateBeneficiaryFormModel::class,
            'beneficiary' => null,
        ]);
    }
}
