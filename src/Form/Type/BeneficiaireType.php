<?php

namespace App\Form\Type;

use App\Entity\Beneficiaire;
use App\Entity\Centre;
use App\Form\DataTransformer\QuestionSecreteTransformer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BeneficiaireType extends AbstractType
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $request = $this->requestStack->getCurrentRequest();
        $builder
            ->add('user', UserBeneficiaireType::class)
            ->add('questionSecrete')
            ->add('autreQuestionSecrete', TextType::class, ['required' => false, 'mapped' => false])
            ->add('reponseSecrete')
            ->add('dateNaissance', BirthdayType::class)
            ->add('centres', EntityType::class, [
                'class' => Centre::class,
                'choices' => $options['centres'],
                'mapped' => false,
                'multiple' => true,
                'expanded' => true,
            ])
            ->addModelTransformer(new QuestionSecreteTransformer($request));

        $builder
            ->get('user')
            ->remove('adresse')
            ->remove('avatar');
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Beneficiaire::class,
            'centres' => null,
            'validation_groups' => ['beneficiaire', 'password'],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 're_form_beneficiaire';
    }
}
