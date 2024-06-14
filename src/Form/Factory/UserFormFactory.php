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
     * Constructor.
     */
    public function __construct(private readonly FormFactoryInterface $formFactory, private readonly AuthorizationCheckerInterface $authorizationChecker)
    {
    }

    public function getBeneficiaireForm($centres)
    {
        if (
            false === $this->authorizationChecker->isGranted('ROLE_ADMIN')
            && false === $this->authorizationChecker->isGranted('ROLE_GESTIONNAIRE')
            && false === $this->authorizationChecker->isGranted('ROLE_MEMBRE')
        ) {
            throw new AccessDeniedException('you_can_not_see_page');
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
