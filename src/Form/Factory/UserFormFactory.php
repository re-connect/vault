<?php

namespace App\Form\Factory;

use App\Entity\Beneficiaire;
use App\Entity\Membre;
use App\Entity\User;
use App\Form\Type\BeneficiaireType;
use App\Form\Type\LoginType;
use App\Form\Type\MembreType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class UserFormFactory
{
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * Constructor.
     */
    public function __construct(FormFactoryInterface $formFactory, AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->formFactory = $formFactory;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @return Form|FormInterface
     */
    public function getLoginForm(RouterInterface $router, $csrfToken)
    {
        return $this->formFactory->create(LoginType::class, null, [
            'router' => $router,
            'csrfToken' => $csrfToken,
        ]);
    }

    public function getBeneficiaireForm($centres)
    {
        if (
            false === $this->authorizationChecker->isGranted('ROLE_ADMIN') &&
            false === $this->authorizationChecker->isGranted('ROLE_GESTIONNAIRE') &&
            false === $this->authorizationChecker->isGranted('ROLE_MEMBRE')
        ) {
            throw new AccessDeniedException('main.pasLesDroits');
        }

        $beneficiaire = new Beneficiaire();
        $user = new User();
        $beneficiaire->setUser($user);

        return $this->formFactory->create(BeneficiaireType::class, $beneficiaire, [
            'centres' => $centres,
            'csrf_protection' => false,
        ]);
    }

    /**
     * @param bool $removeUsername
     *
     * @return Form|FormInterface
     */
    public function getMembreForm($centres, $removeUsername = false)
    {
        if (
            false === $this->authorizationChecker->isGranted('ROLE_ADMIN') &&
            false === $this->authorizationChecker->isGranted('ROLE_GESTIONNAIRE') &&
            false === $this->authorizationChecker->isGranted('ROLE_MEMBRE')
        ) {
            throw new AccessDeniedException('main.pasLesDroits');
        }

        $membre = new Membre();
        $user = new User();
        $membre->setUser($user);

        return $this->formFactory->create(MembreType::class, $membre, ['centres' => $centres, 'removeUsername' => $removeUsername]);
    }
}
