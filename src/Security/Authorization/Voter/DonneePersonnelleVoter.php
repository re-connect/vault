<?php

namespace App\Security\Authorization\Voter;

use App\Entity\AccessToken;
use App\Entity\ClientBeneficiaire;
use App\Entity\DonneePersonnelle;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class DonneePersonnelleVoter extends REVoter
{
    public const DONNEEPERSONNELLE_CREATE = 'DonneePersonnelleCreate';
    public const DONNEEPERSONNELLE_VIEW = 'DonneePersonnelleView';
    public const DONNEEPERSONNELLE_EDIT = 'DonneePersonnelleEdit';
    public const DONNEEPERSONNELLE_DELETE = 'DonneePersonnelleDelete';
    public const DONNEEPERSONNELLE_TOGGLE_ACCESS = 'DonneePersonnelleToggleAccess';
    public const DONNEEPERSONNELLE_REPORT_ABUSE = 'DonneePersonnelleAbuse';

    private function getOriginalEntity($donneePersonnelle)
    {
        return $this->entityManager
            ->getUnitOfWork()
            ->getOriginalEntityData($donneePersonnelle);
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
                self::DONNEEPERSONNELLE_CREATE,
                self::DONNEEPERSONNELLE_VIEW,
                self::DONNEEPERSONNELLE_EDIT,
                self::DONNEEPERSONNELLE_DELETE,
                self::DONNEEPERSONNELLE_REPORT_ABUSE,
                self::DONNEEPERSONNELLE_TOGGLE_ACCESS,
            ]) && $subject instanceof DonneePersonnelle;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if ($this->isClientAllowed()) {
            return true;
        }
        /** get current logged in user */
        /** @var User $user */
        $user = $token->getUser();

        /*
         * Vérification que le client à la droit d'intéragir avec la donnée personnelle
         * (Accès attribués via l'admin)
         * Ce test n'est fonctionnel que pour l'API V2
         * Il renverra true pour les autres version
         */
        if (!$this->accessClient($attribute, $token)) {
            return false;
        }

        $client = $this->apiClientManager->getCurrentOldClient();
        if (!$user instanceof UserInterface && $token instanceof AccessToken && $client) {
            /* Si l'entité est en cours d'édition et que l'original est en privé */
            if (null !== $subject->getId()) {
                $originalEntity = $this->getOriginalEntity($subject);
                if (!empty($originalEntity) && $originalEntity['bPrive']) {
                    return false;
                }
            }
            $clientBeneficiaire = $subject
                ->getBeneficiaire()
                ->getExternalLinks()
                ->filter(static function (ClientBeneficiaire $element) use ($client) {
                    return $element->getClient() === $client;
                })->first();
            if (!$clientBeneficiaire) {
                return false;
            }

            return true;
        }

        /* make sure there is a user object (i.e. that the user is logged in) */
        if (!$user instanceof UserInterface) {
            return false;
        }

        switch ($attribute) {
            case self::DONNEEPERSONNELLE_CREATE:
            case self::DONNEEPERSONNELLE_VIEW:
                if ($user->isBeneficiaire() && $user->getSubjectBeneficiaire() === $subject->getBeneficiaire()) {
                    return true;
                }
                if ($user->isAdministrateur()) {
                    return true;
                }
                if ($user->isMembre()) {
                    $scalar = $this->provider->getScalarBeneficaireForMembre($subject->getBeneficiaire()->getId(), $user->getSubjectMembre()->getId());

                    if ($scalar > 0) {
                        return true;
                    }
                }

                if ($user->isGestionnaire()) {
                    $beneficiaires = $this->provider->getBeneficiairesFromGestionnaire($user->getSubjectGestionnaire());
                    if (in_array($subject->getBeneficiaire(), $beneficiaires)) {
                        return true;
                    }
                }
                break;
            case self::DONNEEPERSONNELLE_EDIT:
                if ($user->isBeneficiaire() && $user->getSubjectBeneficiaire() === $subject->getBeneficiaire()) {
                    return true;
                }
                if ($user->isAdministrateur()) {
                    return true;
                }
                $originalEntity = $this->getOriginalEntity($subject);

                if ($user->isMembre()) {
                    $scalar = $this->provider->getScalarBeneficaireForMembre($subject->getBeneficiaire()->getId(), $user->getSubjectMembre()->getId());

                    if ($scalar > 0 && !$originalEntity['bPrive']) {
                        return true;
                    }
                }

                if ($user->isGestionnaire()) {
                    $beneficiaires = $this->provider->getBeneficiairesFromGestionnaire($user->getSubjectGestionnaire());
                    if (!$originalEntity['bPrive'] && in_array($subject->getBeneficiaire(), $beneficiaires)) {
                        return true;
                    }
                }
                break;
            case self::DONNEEPERSONNELLE_DELETE:
                if ($user->isBeneficiaire() && $user->getSubjectBeneficiaire() === $subject->getBeneficiaire()) {
                    return true;
                }
                if ($user->isMembre()) {
                    $beneficiaires = $this->provider->getBeneficiairesFromMembre($user->getSubjectMembre());
                    if (in_array($subject->getBeneficiaire(), $beneficiaires)) {
                        return true;
                    }
                } elseif ($user->isGestionnaire()) {
                    $beneficiaires = $this->provider->getBeneficiairesFromGestionnaire($user->getSubjectGestionnaire());
                    if (in_array($subject->getBeneficiaire(), $beneficiaires)) {
                        return true;
                    }
                }
                if ($user->isAdministrateur()) {
                    return true;
                }
                break;
            case self::DONNEEPERSONNELLE_REPORT_ABUSE:
                if ($user->isMembre() && !$subject->getBPrive()) {
                    return true;
                }
                if ($user->isGestionnaire() && !$subject->getBPrive()) {
                    return true;
                }
                if ($user->isAdministrateur()) {
                    return true;
                }
                break;
            case self::DONNEEPERSONNELLE_TOGGLE_ACCESS:
                if ($user->isBeneficiaire() && $subject->getBeneficiaire()->getId() === $user->getSubjectBeneficiaire()->getId()) {
                    return true;
                }
                if ($user->isAdministrateur()) {
                    return true;
                }
                if ($user->isMembre()) {
                    $originalEntity = $this->getOriginalEntity($subject);
                    $scalar = $this->provider->getScalarBeneficaireForMembre($subject->getBeneficiaire()->getId(), $user->getSubjectMembre()->getId());

                    if ($scalar > 0 && !$originalEntity['bPrive']) {
                        return true;
                    }
                }
                break;
        }

        return false;
    }
}
