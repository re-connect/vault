<?php

namespace App\Security\Authorization\Voter;

use App\Entity\Centre;
use App\Provider\CentreProvider;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class CentreVoter extends Voter
{
    public const GESTION_CENTRE = 'gestion centre';
    public const PAIEMENT_CENTRE = 'paiement centre';

    /**
     * @var CentreProvider
     */
    private $provider;

    /**
     * CentreVoter constructor.
     */
    public function __construct(CentreProvider $provider)
    {
        $this->provider = $provider;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
            self::GESTION_CENTRE,
            self::PAIEMENT_CENTRE,
        ]) && $subject instanceof Centre;
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
            case self::PAIEMENT_CENTRE:
            case self::GESTION_CENTRE:
                // the data object could have for example a method isPrivate()
                // which checks the Boolean attribute $private
                if ($user->isAssociation()) {
                    foreach ($user->getSubject()->getGestionnaire() as $gestionnaire) {
                        $centres = $this->provider->getCentresFromGestionnaire($gestionnaire);
                        if (in_array($subject, $centres)) {
                            return true;
                        }
                    }
                }
                if ($user->isBeneficiaire()) {
                    return false;
                }
                if ($user->isAdministrateur()) {
                    return true;
                }
                if ($user->isMembre()) {
                    $membresCentres = $user->getSubjectMembre()->getMembresCentres();
                    foreach ($membresCentres as $membreCentre) {
                        if ($membreCentre->getCentre() == $subject) {
                            if (self::GESTION_CENTRE == $attribute) {
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
