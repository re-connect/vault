<?php

namespace App\Admin;

use App\Checker\FeatureFlagChecker;
use App\Domain\TermsOfUse\TermsOfUseHelper;
use App\Entity\Attributes\ResetPasswordRequest;
use App\Entity\User;
use App\ServiceV2\Traits\UserAwareTrait;
use Doctrine\ORM\EntityManagerInterface;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\Form\Type\DateTimePickerType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Constraints\Regex;

class UserSimpleAdmin extends AbstractAdmin
{
    use UserAwareTrait;
    private EntityManagerInterface $entityManager;

    public function __construct(
        ?string $code = null,
        ?string $class = null,
        ?string $baseControllerName = null,
        ?Security $security = null,
        private readonly ?RouterInterface $router = null,
        private readonly ?FeatureFlagChecker $featureFlagChecker = null,
    ) {
        $this->security = $security;
        parent::__construct($code, $class, $baseControllerName);
    }

    public function setEntityManager(EntityManagerInterface $entityManager): void
    {
        $this->entityManager = $entityManager;
    }

    #[\Override]
    protected function configureDefaultSortValues(array &$sortValues): void
    {
        $sortValues[DatagridInterface::PAGE] = 1;
        $sortValues[DatagridInterface::SORT_ORDER] = 'DESC';
        $sortValues[DatagridInterface::SORT_BY] = 'id';
    }

    #[\Override]
    protected function configureFormFields(FormMapper $form): void
    {
        $cgsFeatureDate = $this->featureFlagChecker->isEnabled(TermsOfUseHelper::CGS_FEATURE_FLAG_NAME) ? $this->featureFlagChecker->getEnableDate(TermsOfUseHelper::CGS_FEATURE_FLAG_NAME) : null;
        $form
            ->add('nom')
            ->add('prenom', null, ['label' => 'Prénom'])
            ->add('email', null, ['required' => false, 'attr' => ['autocomplete' => 'off']])
            ->add('telephone', TelType::class, [
                'label' => 'Numéro de portable (attention il faut mettre un +33)',
                'required' => false,
                'constraints' => [new Regex('/^[0-9\+]+$/')],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'required' => false,
                'type' => PasswordType::class,
                'first_options' => ['label' => 'password', 'attr' => ['autocomplete' => 'off']],
                'second_options' => ['label' => 'Confirmer le mot de passe', 'attr' => ['autocomplete' => 'off']],
                'invalid_message' => 'Les mots de passe ne sont pas identiques',
            ])
            ->add('createdAt', DateTimePickerType::class, [
                'label' => 'Date de création',
                'required' => false,
                'attr' => ['read_only' => true],
                'disabled' => true,
            ])
            ->add('lastLogin', DateTimePickerType::class, [
                'label' => 'Dernière connexion',
                'required' => false,
                'attr' => ['read_only' => true],
                'disabled' => true,
            ])
            ->add('cgsAcceptedAt', DateTimePickerType::class, [
                'label' => 'Date d\'acceptation des CGU',
                'required' => false,
                'attr' => ['read_only' => true],
                'disabled' => true,
                'help' => $cgsFeatureDate ? sprintf('Nouvelles CGU en ligne depuis le %s', $cgsFeatureDate->format('d/m/Y')) : '',
            ])
            ->add('test', CheckboxType::class, [
                'label' => 'Compte test',
                'required' => false,
            ])
            ->add('resetPasswordRequest', null, [
                'mapped' => false,
                'label' => 'Réinitialisation du mot de passe',
                'required' => false,
                'disabled' => true,
                'help' => $this->getResetPasswordText(),
                'help_html' => true,
                'attr' => ['read_only' => true, 'style' => 'display:none'],
            ])
            ->add('mfaEnabled', null, [
                'label' => 'mfa_enabled',
                'disabled' => !$this->getUser()?->isSuperAdmin(),
            ])
            ->add('resetMfaRetryCount', null, [
                'mapped' => false,
                'label' => 'Réinitialisation de l\'authentification à double facteur',
                'required' => false,
                'disabled' => true,
                'help' => $this->getMfaResetTest(),
                'help_html' => true,
                'attr' => ['read_only' => true, 'style' => 'display:none'],
            ])
            ->add('mfaMethod', ChoiceType::class, [
                'disabled' => !$this->getUser()?->isSuperAdmin(),
                'required' => false,
                'placeholder' => false,
                'label' => 'mfa_method',
                'choices' => array_combine(User::MFA_METHODS, User::MFA_METHODS),
                'empty_data' => User::MFA_METHOD_EMAIL,
                'expanded' => true,
                'multiple' => false,
            ]);
    }

    #[\Override]
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('id')
            ->add('username')
            ->add('nom')
            ->add('prenom')
            ->add('createdAt');
    }

    #[\Override]
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id', null, ['route' => ['name' => 'edit']])
            ->addIdentifier('nom', null, ['route' => ['name' => 'edit']])
            ->addIdentifier('prenom', null, ['route' => ['name' => 'edit']])
            ->addIdentifier('username', null, ['route' => ['name' => 'edit']])
            ->addIdentifier('createdAt', null, ['route' => ['name' => 'edit']]);
    }

    #[\Override]
    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->remove('create');
    }

    public function getResetPasswordText(): string
    {
        /** @var User $subject */
        $subject = $this->getSubject();
        if (!$subject) {
            return '';
        }
        $passwordRequests = $this->entityManager->getRepository(ResetPasswordRequest::class)->findBy(['user' => $subject]);
        $text = 'Statut : <h5 class="badge bg-blue text-white">Pas de réinitialisation en cours</h5>';

        if (0 < count($passwordRequests)) {
            $passwordRequest = $passwordRequests[0];
            $resetTokenPath = $this->router->generate('unlock_password_reset', ['id' => $subject->getId()]);
            $requestDate = $passwordRequest->getRequestedAt();
            $requestDateString = $requestDate->setTimezone(new \DateTimeZone('Europe/Paris'))->format('H\hi');
            $format = 'Statut : <span class="badge bg-green text-white">En cours de réinitialisation</span><p>Demande de réinitialisation effectuée à %s heure de Paris</p><a class="btn btn-success" href="%s">Permettre une nouvelle demande de réinitialisation</a>';

            $text = sprintf($format, $requestDateString, $resetTokenPath);
        }

        return $text;
    }

    public function getMfaResetTest(): string
    {
        /** @var User $user */
        $user = $this->getSubject();

        $row = 'Statut : <h5 class="badge bg-blue text-white">Pas d\'authentification en cours</h5>';

        if ($user->isMfaEnabled() && 0 < $user->getMfaRetryCount()) {
            $template = 'Statut : <span class="badge bg-green text-white">En cours d\'authentification</span><p>Nombre de renvois d\'un code : %d</p><a class="btn btn-success" href="%s">Réinitialiser le nombre de renvois</a>';
            $row = sprintf(
                $template,
                $user->getMfaRetryCount(),
                $this->router->generate('reset_mfa_retry_count', ['id' => $user->getId()]),
            );
        }

        return $row;
    }
}
