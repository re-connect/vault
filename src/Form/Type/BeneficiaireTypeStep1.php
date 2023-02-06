<?php

namespace App\Form\Type;

use App\Entity\Beneficiaire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BeneficiaireTypeStep1 extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $way = $options['way'];
        $years = [];
        for ($i = 1900; $i < 2016; ++$i) {
            $years[] = $i;
        }

        $builder
            ->add('user', UserBeneficiaireType::class, ['required' => true])
            ->add('dateNaissance', DateType::class, ['required' => true, 'attr' => ['class' => 'datePicker'], 'years' => $years])
            ->add('submit', SubmitType::class, ['label' => 'confirm']);

        $userForm = $builder->get('user');
        $options = $userForm->getOptions();
        if (($key = array_search('password', $options['validation_groups'])) !== false) {
            unset($options['validation_groups'][$key]);
        }
        $builder->add('user', UserBeneficiaireType::class, $options);

        $userForm = $builder->get('user');

        $userForm
            ->remove('adresse')
            ->remove('plainPassword')
            ->remove('avatar');

        if ('remotely' === $way) {
            $userForm->get('telephone')->setRequired(true);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Beneficiaire::class,
            'way' => 'default',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return $this->getName();
    }

    public function getName(): string
    {
        return 're_form_beneficiaire_step1';
    }
}
