<?php

namespace App\Security\Authorization\Voter;

use App\Entity\User;
use App\Provider\CentreProvider;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class UserVoter.
 */
class UserVoter extends Voter
{
    public const GESTION_USER = 'gestion beneficiaire';
    private CentreProvider $provider;
    private BeneficiaireVoter $beneficiaireVoter;
    private MembreVoter $membreVoter;

    /**
     * UserVoter constructor.
     */
    public function __construct(CentreProvider $provider)
    {
        $this->provider = $provider;
    }

    public function setBeneficiaireVoter(BeneficiaireVoter $beneficiaireVoter)
    {
        $this->beneficiaireVoter = $beneficiaireVoter;
    }

    public function setMembreVoter(MembreVoter $membreVoter)
    {
        $this->membreVoter = $membreVoter;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
                self::GESTION_USER,
            ]) && $subject instanceof UserInterface;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        // get current logged in user
        /** @var User $user */
        $user = $token->getUser();

        // make sure there is a user object (i.e. that the user is logged in)
        if (!$subject instanceof UserInterface) {
            return false;
        }

        switch ($attribute) {
            case self::GESTION_USER:
                // the data object could have for example a method isPrivate()
                // which checks the Boolean attribute $private
                if ($user === $subject) {
                    return true;
                }
                if ($user->isAdministrateur()) {
                    return true;
                }
                if ($user->isMembre()) {
                    if ($subject->isBeneficiaire()) {
                        return $this->beneficiaireVoter->voteOnAttribute(BeneficiaireVoter::GESTION_BENEFICIAIRE, $subject->getSubjectBeneficiaire(), $token);
                    }
                    if ($subject->isMembre()) {
                        return $this->membreVoter->voteOnAttribute(MembreVoter::GESTION_MEMBRE, $subject->getSubjectMembre(), $token);
                    }
                }

                break;
        }

        return false;
    }
}
