<?php

namespace App\Form\Factory;

use App\Entity\Beneficiaire;
use App\Entity\User;
use App\Form\Type\BeneficiaireType;
use Symfony\Component\Form\FormFactoryInterface;
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

    public function getBeneficiaireForm($centres)
    {
        if (
            false === $this->authorizationChecker->isGranted('ROLE_ADMIN')
            && false === $this->authorizationChecker->isGranted('ROLE_GESTIONNAIRE')
            && false === $this->authorizationChecker->isGranted('ROLE_MEMBRE')
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
}
