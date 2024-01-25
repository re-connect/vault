<?php

namespace App\FormV2;

use App\Entity\Beneficiaire;
use App\Entity\User;
use App\EventSubscriber\AddFormattedPhoneSubscriber;
use App\FormV2\UserCreation\SecretQuestionType;
use App\ServiceV2\Traits\UserAwareTrait;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    use UserAwareTrait;

    private const NAME_REGEX = "^[a-zA-ZáàâäãåąçčćęéèêëėįíìîïłńñóòôöõøšúùûüųýÿżźžÁÀÂÄÃÅĄÇČĆĘÉÈÊËĖÍÌÎÏŁĮŃÑÓÒÔÖÕØŠÚÙÛÜŲÝŸŽ \-']+$";

    public function __construct(private readonly Security $security)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addDefaultFields($builder);
        if ($beneficiary = $this->getUser()?->getSubjectBeneficiaire()) {
            $this->addBeneficiaryFields($builder, $beneficiary);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'validation_groups' => ['beneficiaire', 'membre', 'phone'],
            'allow_extra_fields' => true,
        ]);
    }

    private function addBeneficiaryFields(FormBuilderInterface $builder, Beneficiaire $beneficiary): void
    {
        $builder
            ->add('adresse', AddressType::class, [
                'required' => false,
                'label' => 'your_address',
            ])
            ->add('dateNaissance', BirthdayType::class, [
                'property_path' => 'subjectBeneficiaire.dateNaissance',
                'required' => false,
                'label' => 'birthDate',
            ])
            ->add('secretQuestion', SecretQuestionType::class, [
                'property_path' => 'subjectBeneficiaire',
                'label' => false,
                'data' => $beneficiary,
            ]);
    }

    public function addDefaultFields(FormBuilderInterface $builder): void
    {
        $builder
            ->add('prenom', null, [
                'label' => 'firstname',
                'attr' => ['pattern' => self::NAME_REGEX],
                'row_attr' => ['class' => 'col-6 mt-3'],
            ])
            ->add('nom', null, [
                'label' => 'lastname',
                'attr' => ['pattern' => self::NAME_REGEX],
                'row_attr' => ['class' => 'col-6 mt-3'],
            ])
            ->add('telephone', null, [
                'required' => false,
                'label' => 'phone',
                'row_attr' => [
                    'class' => 'col-6 mt-3',
                    'data-controller' => 'intl-tel-input',
                ],
                'attr' => [
                    'data-intl-tel-input-target' => 'input',
                    'autocomplete' => 'tel',
                ],
            ])
            ->addEventSubscriber(new AddFormattedPhoneSubscriber())
            ->add('email', EmailType::class, [
                'required' => false,
                'row_attr' => ['class' => 'col-6 mt-3'],
                'label' => 'email',
            ])
            ->add('mfaEnabled', CheckboxType::class, [
                'required' => false,
                'label' => 'enable_mfa',
                'help' => 'enable_mfa_help',
            ]);
    }
}
