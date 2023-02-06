<?php

namespace App\Form\Type;

use App\Entity\User;
use App\Form\Entity\PasswordResetSecretQuestion;
use App\Manager\UserManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class PasswordResetSecretQuestionType extends AbstractType
{
    private UserManager $manager;

    public function __construct(UserManager $manager)
    {
        $this->manager = $manager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $manager = $this->manager;
        /** @var User $user */
        $user = $options['user'];
        $correctAnswer = $user->getSecretAnswer();

        $builder
            ->add('answer', TextType::class, [
                'label' => 'secret_answer',
                'constraints' => [
                    new Callback(function ($answer, ExecutionContextInterface $context) use ($correctAnswer, $manager) {
                        if (!$manager->compareSecretStrings($correctAnswer, $answer)) {
                            $context
                                ->buildViolation('membre.resettingBeneficiairePassword.erreur')
                                ->setTranslationDomain('messages')
                                ->addViolation();
                        }
                    }),
                ],
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => ['label' => 'user.parametres.nouveauMotDePasse'],
                'second_options' => ['label' => 'user.parametres.nouveauMotDePasseConfirm'],
                'invalid_message' => 'fos_user.password.mismatch',
                'attr' => ['class' => 'border-blue-secondary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => PasswordResetSecretQuestion::class,
                'validation_groups' => ['password', 'Default'],
            ])
            ->setRequired(['user'])
            ->setAllowedTypes('user', User::class);
    }
}
