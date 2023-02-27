<?php

namespace App\FormV2;

use App\Entity\Beneficiaire;
use App\Entity\User;
use App\EventSubscriber\AddFormattedPhoneSubscriber;
use App\Form\Event\SecretQuestionListener;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserSettingsType extends AbstractType
{
    private Security $security;
    private const NAME_REGEX = "^[a-zA-ZáàâäãåąçčćęéèêëėįíìîïłńñóòôöõøšúùûüųýÿżźžÁÀÂÄÃÅĄÇČĆĘÉÈÊËĖÍÌÎÏŁĮŃÑÓÒÔÖÕØŠÚÙÛÜŲÝŸŽ \-']+$";
    private TranslatorInterface $translator;

    public function __construct(Security $security, TranslatorInterface $translator)
    {
        $this->security = $security;
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $builder
            ->add('prenom', null, [
                'label' => 'registerForm.prenom',
                'attr' => [
                    'pattern' => self::NAME_REGEX,
                ],
            ])
            ->add('nom', null, [
                'label' => 'registerForm.nom',
                'attr' => [
                    'pattern' => self::NAME_REGEX,
                ],
            ])
            ->add('telephone', null, [
                'required' => false,
                'label' => 'registerForm.telephone',
                'attr' => [
                    'class' => 'intl-tel-input',
                ],
            ])
            ->addEventSubscriber(new AddFormattedPhoneSubscriber())
            ->add('email', EmailType::class, [
                'required' => false,
                'label' => 'registerForm.email',
            ]);

        if ($user->isBeneficiaire()) {
            $secretQuestions = [];
            foreach (Beneficiaire::getArQuestionsSecrete() as $key => $value) {
                $secretQuestions[$this->translator->trans($key)] = $this->translator->trans($value);
            }

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
                ->add('questionSecreteChoice', ChoiceType::class, [
                    'required' => true,
                    'label' => 'secret_question',
                    'choices' => $secretQuestions,
                    'mapped' => false,
                ])
                ->add('questionSecrete', HiddenType::class, [
                    'required' => true,
                    'property_path' => 'subjectBeneficiaire.questionSecrete',
                    'label' => 'user.parametres.questionSecreteLabel',
                ])
                ->add('autreQuestionSecrete', TextType::class, [
                    'property_path' => 'subjectBeneficiaire.autreQuestionSecrete',
                    'required' => false,
                    'label' => 'secret_question_other',
                    'mapped' => false,
                ])
                ->add('reponseSecrete', TextType::class, [
                    'property_path' => 'subjectBeneficiaire.reponseSecrete',
                    'label' => 'user.parametres.reponseSecreteLabel',
                ])
                ->addEventSubscriber(new SecretQuestionListener($this->translator));
        } elseif ($user->hasMemberAccess()) {
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
