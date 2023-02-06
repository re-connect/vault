<?php

namespace App\Extension;

use App\Entity\Beneficiaire;
use App\Entity\Centre;
use App\Entity\User;
use App\Provider\CentreProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class NotificationExtension extends AbstractExtension
{
    public const NOTIFICATION_AJOUT_CENTRE = 'NOTIFICATION_AJOUT_CENTRE';
    public const NOTIFICATION_AJOUT_CONTACT = 'NOTIFICATION_AJOUT_CONTACT';

    private TokenStorageInterface $tokenStorage;
    private CentreProvider $centreProvider;
    private EntityManagerInterface $entityManager;
    private TranslatorInterface $translator;
    private RouterInterface $router;
    private RequestStack $requestStack;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        CentreProvider $centreProvider,
        RequestStack $requestStack,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        RouterInterface $router
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->centreProvider = $centreProvider;
        $this->entityManager = $entityManager;
        $this->translator = $translator;
        $this->router = $router;
        $this->requestStack = $requestStack;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getNotifications', [$this, 'getNotifications']),
        ];
    }

    /**
     * @throws \Exception
     */
    public function getNotifications(): array
    {
        /* @var Centre $pendingCentre */
        if (!is_object($this->tokenStorage->getToken())) {
            return [];
        }
        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();
        if (null === $user || is_string($user)) {
            return [];
        }

        if ($user->isBeneficiaire() || $user->isMembre()) {
            $subject = null;
            if ($user->isBeneficiaire()) {
                $subject = $user->getSubjectBeneficiaire();
            }
            if ($user->isMembre()) {
                $subject = $user->getSubjectMembre();
            }

            $arStr = [];
            if (null !== $subject) {
                /** NOTIFICATION PENDING CENTRES */
                $pendingCentres = $this->centreProvider->getPendingCentresFromUserWithCentre($subject);

                foreach ($pendingCentres as $pendingCentre) {
                    $arStr[] = [
                        'type' => self::NOTIFICATION_AJOUT_CENTRE,
                        'centre' => $pendingCentre,
                        'title' => $this->translator->trans('user.pendingCentre.title', ['%centre%' => $pendingCentre->getNom()]),
                        'okLink' => $this->router->generate('re_user_accepterCentre', ['id' => $pendingCentre->getId()]),
                        'cancelLink' => $this->router->generate('re_user_refuserCentre', ['id' => $pendingCentre->getId()]),
                    ];

                    // todo: actuellement une seule notification
                    break;
                }
            }

            $session = $this->requestStack->getSession();

            /*
             * NOTIFICATION CONSULTATIONBENEFICIAIRE
             * Notifications pour l'ajout d'un membre dans les contacts d'un utilisateur
             */
            if ($user->isMembre() &&
                $session->has('firstConsultationBeneficiaire') &&
                $beneficiaire = $this->entityManager->find(Beneficiaire::class, $session->get('firstConsultationBeneficiaire'))
            ) {
                $arStr[] = [
                    'type' => self::NOTIFICATION_AJOUT_CONTACT,
                    'beneficiaire' => $beneficiaire,
                    'title' => $this->translator->trans('membre.partageContact.title'),
                    'okLink' => $this->router->generate('re_membre_ajoutContactBeneficiaire', ['id' => $beneficiaire->getId()]),
                    'cancelLink' => null,
                ];
                $session->remove('firstConsultationBeneficiaire');
            }

            return $arStr;
        }

        return [];
    }

    public function getName(): string
    {
        return 'NotificationsExtensions';
    }
}
