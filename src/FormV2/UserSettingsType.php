<?php

namespace App\FormV2;

use App\Entity\User;
use App\EventSubscriber\AddFormattedPhoneSubscriber;
use App\FormV2\UserCreation\SecretQuestionType;
use App\ServiceV2\Traits\UserAwareTrait;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserSettingsType extends AbstractType
{
    use UserAwareTrait;
    private const NAME_REGEX = "^[a-zA-ZáàâäãåąçčćęéèêëėįíìîïłńñóòôöõøšúùûüųýÿżźžÁÀÂÄÃÅĄÇČĆĘÉÈÊËĖÍÌÎÏŁĮŃÑÓÒÔÖÕØŠÚÙÛÜŲÝŸŽ \-']+$";

    public function __construct(
        private readonly Security $security,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('prenom', null, [
                'label' => 'firstname',
                'attr' => [
                    'pattern' => self::NAME_REGEX,
                ],
            ])
            ->add('nom', null, [
                'label' => 'lastname',
                'attr' => [
                    'pattern' => self::NAME_REGEX,
                ],
            ])
            ->add('telephone', null, [
                'required' => false,
                'label' => 'phone',
                'attr' => [
                    'class' => 'intl-tel-input',
                ],
            ])
            ->addEventSubscriber(new AddFormattedPhoneSubscriber())
            ->add('email', EmailType::class, [
                'required' => false,
                'label' => 'registerForm.email',
            ]);

        if ($beneficiary = $this->getUser()?->getSubjectBeneficiaire()) {
            $builder
                ->add('adresse', AddressType::class, [
                    'required' => false,
                    'label' => 'registerForm.adresse',
                ])
                ->add('dateNaissance', BirthdayType::class, [
                    'property_path' => 'subjectBeneficiaire.dateNaissance',
                    'required' => false,
                    'label' => 'user.parametres.dateNaissanceLabel',
                ])
                ->add('secretQuestion', SecretQuestionType::class, [
                    'property_path' => 'subjectBeneficiaire',
                    'label' => false,
                    'data' => $beneficiary,
                ]);
        } else {
            $builder
                ->add('username', TextType::class, [
                    'label' => 'registerForm.username',
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'validation_groups' => ['beneficiaire', 'membre'],
            'allow_extra_fields' => true,
        ]);
    }
}
