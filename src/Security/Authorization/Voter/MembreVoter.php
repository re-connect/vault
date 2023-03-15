<?php

namespace App\Security\Authorization\Voter;

use App\Entity\Membre;
use App\Entity\MembreCentre;
use App\Provider\CentreProvider;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class MembreVoter extends Voter
{
    public const GESTION_MEMBRE = 'gestion membre';

    private $provider;

    public function __construct(CentreProvider $provider)
    {
        $this->provider = $provider;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
            self::GESTION_MEMBRE,
        ]) && $subject instanceof Membre;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        // get current logged in user
        $user = $token->getUser();

        // make sure there is a user object (i.e. that the user is logged in)
        if (!$user instanceof UserInterface) {
            return false;
        }
        switch ($attribute) {
            case self::GESTION_MEMBRE:
                // the data object could have for example a method isPrivate()
                // which checks the Boolean attribute $private

                if ($user->isBeneficiaire()) {
                    return false;
                }
                if ($user->isAdministrateur()) {
                    return true;
                }
                if ($user->isMembre()) {
                    if ($user === $subject->getUser()) {
                        return true;
                    }

                    $membresByCentre = $this->provider->getOtherMembresFromMembreByCentre($user->getSubjectMembre());

                    foreach ($membresByCentre as $idCentre => $arMembresCentre) {
                        $centre = $arMembresCentre['centre'];

                        // D'abord vérifier que le membre a les droits sur le centre
                        $currentMembreCentre = $user->getSubjectMembre()->getUserCentre($centre);
                        if (null === $currentMembreCentre || null === $currentMembreCentre->getDroits() || !in_array(MembreCentre::TYPEDROIT_GESTION_MEMBRES, $currentMembreCentre->getDroits()) || false == $currentMembreCentre->getDroits()[MembreCentre::TYPEDROIT_GESTION_MEMBRES]) {
                            return false;
                        }

                        // Ensuite, vérifier que le centre accueil le membre sur lequel des modifs sont demandées
                        foreach ($arMembresCentre['otherMembres'] as $otherMembre) {
                            if ($otherMembre->getId() === $subject->getId()) {
                                return true;
                            }
                        }
                    }
                }

                break;
        }

        return false;
    }
}
