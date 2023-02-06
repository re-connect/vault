<?php

namespace App\Security\Authorization\Voter;

use App\Entity\Beneficiaire;
use App\Entity\MembreCentre;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class BeneficiaireVoter extends REVoter
{
    public const GESTION_BENEFICIAIRE = 'gestion beneficiaire';
    public const ASSOCIATION_BENEFICIAIRE = 'association beneficiaire';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
                self::GESTION_BENEFICIAIRE,
                self::ASSOCIATION_BENEFICIAIRE,
            ]) && $subject instanceof Beneficiaire;
    }

    protected function voteOnAttribute(string $attribute, mixed $beneficiaire, TokenInterface $token): bool
    {
        // get current logged in user
        $user = $token->getUser();

        // make sure there is a user object (i.e. that the user is logged in)
        if (!$user instanceof UserInterface) {
            return false;
        }

        if ($this->isClientAllowed()) {
            return true;
        }

        switch ($attribute) {
            case self::ASSOCIATION_BENEFICIAIRE:
            case self::GESTION_BENEFICIAIRE:
                // the data object could have for example a method isPrivate()
                // which checks the Boolean attribute $private
                if ($user->isBeneficiaire() && $user->getSubjectBeneficiaire() === $beneficiaire) {
                    return true;
                }
                if ($user->isAdministrateur()) {
                    return true;
                }

                if ($user->isMembre()) {
                    $beneficiairesByCentre = $this->provider->getBeneficiairesFromMembreByCentre($user->getSubjectMembre(), true);
                    foreach ($beneficiairesByCentre as $idCentre => $arBeneficiairesCentre) {
                        $centre = $arBeneficiairesCentre['centre'];

                        // D'abord vérifier que le membre a les droits sur le centre
                        $currentMembreCentre = $user->getSubjectMembre()->getUserCentre($centre);
                        if (null === $currentMembreCentre || null === $currentMembreCentre->getDroits() || !in_array(MembreCentre::TYPEDROIT_GESTION_BENEFICIAIRES, $currentMembreCentre->getDroits()) || false === $currentMembreCentre->getDroits()[MembreCentre::TYPEDROIT_GESTION_BENEFICIAIRES]) {
                            return false;
                        }

                        // Ensuite, vérifier que le centre accueille le membre sur lequel des modifs sont demandées
                        foreach ($arBeneficiairesCentre['beneficiaires'] as $value) {
                            if ($value === $beneficiaire) {
                                return true;
                            }
                        }

                        // Si le beneficiaire n'appartient à aucun centre, on a le droit de l'ajouter
                        if ($beneficiaire->getIsCreating()) {
                            return true;
                        }
                    }
                }
                if ($user->isGestionnaire()) {
                    $beneficiaires = $this->provider->getBeneficiairesFromGestionnaire($user->getSubjectGestionnaire());
                    if (self::ASSOCIATION_BENEFICIAIRE === $attribute || in_array($beneficiaire, $beneficiaires, true)) {
                        return true;
                    }
                }

                break;
        }

        return false;
    }
}
