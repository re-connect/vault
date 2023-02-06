<?php

namespace App\Form\Type;

use App\Entity\Beneficiaire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BeneficiaireTypeStep2 extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('user', UserMinimalType::class, ['required' => true])
            ->add('submit', SubmitType::class, ['label' => 'confirm']);

        $field = $builder->get('user')->get('plainPassword');
        $autoPassword = $options['autoPassword'];
        $options = [];
        $options['data'] = $autoPassword;
        $options['label'] = 'registerForm.motDePasse';
        $builder->get('user')->add('plainPassword', TextType::class, $options); // replace the field

        $field = $builder->get('user')->get('username');
        $options = $field->getOptions();
        $type = $field->getType()->getBlockPrefix();

        $options['disabled'] = 'disabled';
        $options['attr']['read_only'] = 'read_only';
        $builder->get('user')->add('username', TextType::class, $options); // replace the field
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Beneficiaire::class,
            'validation_groups' => ['password'],
            'autoPassword' => null,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return $this->getName();
    }

    public function getName(): string
    {
        return 're_form_beneficiaire_step2';
    }
}
