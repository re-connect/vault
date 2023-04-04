<?php

namespace App\Manager;

use App\Entity\BeneficiaireCentre;
use App\Entity\Centre;
use App\Entity\ClientBeneficiaire;
use App\Entity\Membre;
use App\Entity\MembreCentre;
use App\Entity\UserHandleCentresInterface;
use App\Entity\UserWithCentresInterface;
use App\Event\CentreEvent;
use App\Event\REEvent;
use App\Provider\CentreProvider;
use App\Security\Authorization\Voter\BeneficiaireVoter;
use App\Security\Authorization\Voter\MembreVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class CentreManager.
 */
class CentreManager
{
    private AuthorizationCheckerInterface $authorizationChecker;
    private EventDispatcherInterface $eventDispatcher;
    private EntityManagerInterface $em;
    private CentreProvider $provider;

    /**
     * Constructor.
     */
    public function __construct(
        CentreProvider $provider,
        EntityManagerInterface $em,
        EventDispatcherInterface $eventDispatcher,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->provider = $provider;
        $this->em = $em;
        $this->eventDispatcher = $eventDispatcher;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @param null $arDroits
     * @param bool $bForceAccept
     *
     * @return bool
     */
    public function associateUserWithCentres($subject, Centre $centre, $initiateur, $arDroits = null, $bForceAccept = false)
    {
        foreach ($subject->getUsersCentres() as $userCentre) {
            if ($userCentre->getCentre() === $centre) {
                return false;
            }
        }

        if ($subject->isBeneficiaire()) {
            $userCentre = (new BeneficiaireCentre())->setBeneficiaire($subject);
            $subject->addBeneficiairesCentre($userCentre);
        } elseif ($subject->isMembre()) {
            $userCentre = (new MembreCentre())->setMembre($subject)->setDroits($arDroits);
        } else {
            throw new \RuntimeException('Subject is not member nor beneficiary');
        }

        $userCentre->setBValid($bForceAccept)->setCentre($centre);

        if (null !== $initiateur) {
            $userCentre->setInitiateur($initiateur);
        }
        $this->em->persist($userCentre);
        $this->em->flush();

        $this->eventDispatcher->dispatch(new CentreEvent($centre, CentreEvent::CENTRE_USERWITHCENTRES_ASSOCIATED, $subject), REEvent::RE_EVENT_CENTRE);

        return true;
    }

    public function deassociateUserWithCentres($subject, Centre $centre)
    {
        if ($subject->isBeneficiaire() && false === $this->authorizationChecker->isGranted(BeneficiaireVoter::GESTION_BENEFICIAIRE, $subject)) {
            throw new AccessDeniedException("Vous n'avez pas le droit de désassocier ce beneficiaire de ce centre");
        }

        if ($subject->isMembre() && false === $this->authorizationChecker->isGranted(MembreVoter::GESTION_MEMBRE, $subject)) {
            throw new AccessDeniedException("Vous n'avez pas le droit de désassocier ce membre de ce centre");
        }

        $centres = $this->provider->getCentresFromUserWithCentre($subject);
        if (!in_array($centre, $centres)) {
            throw new \RuntimeException("Le sujet n'est pas suivi dans ce centre");
        }

        foreach ($subject->getUsersCentres() as $userCentre) {
            if ($userCentre->getCentre() === $centre) {
                if ($userCentre instanceof BeneficiaireCentre) {
                    $externalLink = $this->em->getRepository(ClientBeneficiaire::class)->findOneByBeneficiaireCentre($userCentre);
                    if (null !== $externalLink) {
                        $this->em->remove($externalLink);
                    }
                }
                $this->em->remove($userCentre);
            }
        }
        $this->em->flush();

        $this->eventDispatcher->dispatch(new CentreEvent($centre, CentreEvent::CENTRE_USERWITHCENTRES_DESASSOCIATED, $subject), REEvent::RE_EVENT_CENTRE);
    }

    /**
     * @throws \Exception
     */
    public function accepterTousCentreEnCommun($subject, $subject2): void
    {
        if ($subject->isBeneficiaire() && false === $this->authorizationChecker->isGranted(BeneficiaireVoter::ASSOCIATION_BENEFICIAIRE, $subject)) {
            throw new AccessDeniedException("Vous n'avez pas le droit d'associer ce beneficiaire à ce centre");
        }

        if ($subject->isMembre() && false === $this->authorizationChecker->isGranted(MembreVoter::GESTION_MEMBRE, $subject)) {
            throw new AccessDeniedException("Vous n'avez pas le droit d'associer ce membre de ce centre");
        }

        $centresEnCommun = $this->getCentreCommunUserWithCentres($subject, $subject2);

        foreach ($subject->getUsersCentres() as $usersCentre) {
            if (in_array($usersCentre->getCentre(), $centresEnCommun, true)) {
                $usersCentre->setBValid(true);
                $this->em->persist($usersCentre);
            }
        }
        $subject->setActivationSmsCode(null)->setActivationSmsCodeLastSend(null);

        $this->em->flush();
    }

    /**
     * @param UserHandleCentresInterface $subject2
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getCentreCommunUserWithCentres($subject, $subject2)
    {
        $arRet = [];
        $centres1 = $this->provider->getCentresFromUserWithCentre($subject);
        $centres2 = $subject2->getHandledCentres();
        if (is_object($centres2)) {
            $centres2 = $centres2->toArray();
        }

        foreach ($centres1 as $centre) {
            if (in_array($centre, $centres2)) {
                $arRet[] = $centre;
            }
        }

        return $arRet;
    }

    public function accepterCentre(UserWithCentresInterface $subject, Centre $centre)
    {
        foreach ($subject->getUsersCentres() as $userCentre) {
            if ($userCentre->getCentre() === $centre) {
                $userCentre->setBValid(true);
                $this->em->persist($userCentre);
                $this->em->flush();
                break;
            }
        }
    }

    public function refuserCentre(UserWithCentresInterface $subject, Centre $centre)
    {
        foreach ($subject->getUsersCentres() as $userCentre) {
            if ($userCentre->getCentre() === $centre) {
                $this->em->remove($userCentre);
                $this->em->flush();
                break;
            }
        }
    }

    public function switchDroitMembreCentre(Membre $membre, Centre $centre, $droit)
    {
        if ($membreCentre = $membre->getUserCentre($centre)) {
            if (null === $membreCentre->getDroits()) {
                $membreCentre->setDroits();
            }

            $droits = $membreCentre->getDroits();
            if (count($droits) > 0 && !is_string(array_keys($droits)[0])) {
                $droits = array_flip($droits);
            }
            if (!array_key_exists($droit, $droits)) {
                $droits[$droit] = true;
            } else {
                $droits[$droit] = !$droits[$droit];
            }

            $membreCentre->setDroits($droits);
            $this->em->persist($membreCentre);
            $this->em->flush();
        }
    }
}
