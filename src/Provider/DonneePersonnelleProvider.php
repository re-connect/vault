<?php

namespace App\Provider;

use App\Api\Manager\ApiClientManager;
use App\Entity\Beneficiaire;
use App\Entity\Centre;
use App\Entity\Client;
use App\Entity\Contact;
use App\Entity\CreatorCentre;
use App\Entity\CreatorClient;
use App\Entity\CreatorUser;
use App\Entity\Document;
use App\Entity\DonneePersonnelle;
use App\Entity\Dossier;
use App\Entity\Evenement;
use App\Entity\Note;
use App\Entity\User;
use App\Event\DonneePersonnelleEvent;
use App\Event\REEvent;
use App\Exception\JsonResponseException;
use App\Repository\ClientRepository as OldClientRepository;
use App\Security\Authorization\Voter\BeneficiaireVoter;
use App\Security\Authorization\Voter\DonneePersonnelleVoter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use League\Bundle\OAuth2ServerBundle\Repository\ClientRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class DonneePersonnelleProvider
{
    protected string $formType;
    protected string $formSimpleType;
    protected string $entityName;
    protected ?Request $request;

    public function __construct(
        protected FormFactoryInterface $formFactory,
        protected AuthorizationCheckerInterface $authorizationChecker,
        protected EntityManagerInterface $em,
        protected TokenStorageInterface $tokenStorage,
        protected EventDispatcherInterface $eventDispatcher,
        protected ValidatorInterface $validator,
        protected TranslatorInterface $translator,
        protected RequestStack $requestStack,
        protected OldClientRepository $oldClientRepository,
        protected ClientRepository $clientRepository,
        protected ApiClientManager $apiClientManager,
    ) {
        $this->request = $requestStack->getCurrentRequest();
    }

    public function getEntitiesFromBeneficiaire(Beneficiaire $beneficiaire): array
    {
        if (false === $this->authorizationChecker->isGranted(BeneficiaireVoter::GESTION_BENEFICIAIRE, $beneficiaire)) {
            throw new AccessDeniedException('donneePersonnelle.cantDisplay');
        }

        $qb = $this->em->createQueryBuilder()
            ->select('d')
            ->from($this->entityName, 'd')
            ->innerJoin('d.beneficiaire', 'b')
            ->where('b.id = '.$beneficiaire->getId());

        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        if ($user->isMembre()) {
            $qb->andWhere('d.bPrive = false');
        }

        return $qb
            ->getQuery()
            ->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
            ->getResult();
    }

    public function getForm(DonneePersonnelle $donneePersonnelle): FormInterface
    {
        $attribute = null === $donneePersonnelle->getId() ? DonneePersonnelleVoter::DONNEEPERSONNELLE_CREATE : DonneePersonnelleVoter::DONNEEPERSONNELLE_EDIT;
        $user = $this->tokenStorage->getToken()->getUser();
        if (!$user instanceof User || false === $this->authorizationChecker->isGranted($attribute, $donneePersonnelle)) {
            throw new AccessDeniedException($this->translator->trans('donneePersonnelle.cantEdit'));
        }

        if ($user->isBeneficiaire()) {
            $class = $this->formSimpleType;
        } else {
            $class = $this->formType;
        }

        return $this->formFactory->createBuilder($class, $donneePersonnelle)->getForm();
    }

    public function delete(DonneePersonnelle $donneePersonnelle, bool $log = true): void
    {
        if (false === $this->authorizationChecker->isGranted(DonneePersonnelleVoter::DONNEEPERSONNELLE_DELETE, $donneePersonnelle)) {
            throw new AccessDeniedException($this->translator->trans('donneePersonnelle.cantDelete'));
        }

        if ($log) {
            $this->eventDispatcher->dispatch(
                new DonneePersonnelleEvent($donneePersonnelle, $donneePersonnelle->getBeneficiaire()->getUser(), DonneePersonnelleEvent::DONNEEPERSONNELLE_DELETED),
                REEvent::RE_EVENT_DONNEEPERSONNELLE,
            );
        }

        $this->em->remove($donneePersonnelle);
        $this->em->flush();
    }

    public function reportAbuse(DonneePersonnelle $donneePersonnelle): bool
    {
        if (false === $this->authorizationChecker->isGranted(
            DonneePersonnelleVoter::DONNEEPERSONNELLE_REPORT_ABUSE,
            $donneePersonnelle
        )
        ) {
            throw new AccessDeniedException($this->translator->trans('donneePersonnelle.cantReportAbuse'));
        }

        // TODO: Mettre un log

        $donneePersonnelle->setBPrive(true);
        $this->em->persist($donneePersonnelle);
        $this->em->flush();

        return true;
    }

    public function save(DonneePersonnelle $donneePersonnelle): void
    {
        if (null === $donneePersonnelle->getId()) {
            if (false === $this->authorizationChecker->isGranted(DonneePersonnelleVoter::DONNEEPERSONNELLE_CREATE, $donneePersonnelle)) {
                throw new AccessDeniedException($this->translator->trans('donneePersonnelle.cantCreate'));
            }
            $this->eventDispatcher->dispatch(new DonneePersonnelleEvent($donneePersonnelle, $donneePersonnelle->getBeneficiaire()->getUser(), DonneePersonnelleEvent::DONNEEPERSONNELLE_CREATED), REEvent::RE_EVENT_DONNEEPERSONNELLE);
        } else {
            if (false === $this->authorizationChecker->isGranted(DonneePersonnelleVoter::DONNEEPERSONNELLE_EDIT, $donneePersonnelle)) {
                throw new AccessDeniedException($this->translator->trans('donneePersonnelle.cantEdit'));
            }
            $this->eventDispatcher->dispatch(new DonneePersonnelleEvent($donneePersonnelle, $donneePersonnelle->getBeneficiaire()->getUser(), DonneePersonnelleEvent::DONNEEPERSONNELLE_MODIFIED), REEvent::RE_EVENT_DONNEEPERSONNELLE);
        }

        $this->em->persist($donneePersonnelle);
        $this->em->flush();
    }

    public function changePrive(DonneePersonnelle $donneePersonnelle, $bPrive = true): void
    {
        if (false === $this->authorizationChecker->isGranted(DonneePersonnelleVoter::DONNEEPERSONNELLE_TOGGLE_ACCESS, $donneePersonnelle)) {
            throw new AccessDeniedException($this->translator->trans('donneePersonnelle.cantToggleAccess'));
        }

        // TODO: Pour le log, mettre le vrai user qui a fait l'action
        if (!$bPrive || $donneePersonnelle->getBPrive()) {
            $donneePersonnelle->setBPrive(false);
            $this->eventDispatcher->dispatch(
                new DonneePersonnelleEvent($donneePersonnelle, $donneePersonnelle->getBeneficiaire()->getUser(), DonneePersonnelleEvent::DONNEEPERSONNELLE_SETPUBLIC),
                REEvent::RE_EVENT_DONNEEPERSONNELLE,
            );
        } else {
            $donneePersonnelle->setBPrive(true);
            $this->eventDispatcher->dispatch(
                new DonneePersonnelleEvent($donneePersonnelle, $donneePersonnelle->getBeneficiaire()->getUser(), DonneePersonnelleEvent::DONNEEPERSONNELLE_SETPRIVE),
                REEvent::RE_EVENT_DONNEEPERSONNELLE,
            );
        }
        $this->em->persist($donneePersonnelle);
        $this->em->flush();
    }

    public function rename(DonneePersonnelle $donneePersonnelle, $newName)
    {
        if (false === $this->authorizationChecker->isGranted(DonneePersonnelleVoter::DONNEEPERSONNELLE_EDIT, $donneePersonnelle)) {
            throw new AccessDeniedException($this->translator->trans('donneePersonnelle.cantEdit'));
        }

        $sanitizedString = $this->sanitize($newName);

        $donneePersonnelle->setNom($sanitizedString);
        $this->em->persist($donneePersonnelle);
        $this->em->flush();

        $this->eventDispatcher->dispatch(new DonneePersonnelleEvent($donneePersonnelle, $donneePersonnelle->getBeneficiaire()->getUser(), DonneePersonnelleEvent::DONNEEPERSONNELLE_MODIFIED), REEvent::RE_EVENT_DONNEEPERSONNELLE);

        return $sanitizedString;
    }

    public function sanitize($str, $charset = 'utf-8')
    {
        $str = htmlentities($str, ENT_NOQUOTES, $charset);
        $str = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
        $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str); // pour les ligatures e.g. '&oelig;'
        $str = preg_replace('#&[^;]+;#', '', $str); // supprime les autres caractères
        $str = preg_replace('#[^0-9-_a-zA-Z ().]#', '', $str); // supprime les autres caractères

        return $str;
    }

    public function getEntitiesByName(Beneficiaire $beneficiaire, $name): array
    {
        if (false === $this->authorizationChecker->isGranted(BeneficiaireVoter::GESTION_BENEFICIAIRE, $beneficiaire)) {
            throw new AccessDeniedException('donneePersonnelle.cantDisplay');
        }

        $qb = $this->em->createQueryBuilder()
            ->select('d')
            ->from($this->entityName, 'd')
            ->innerJoin('d.beneficiaire', 'b')
            ->where('b.id = '.$beneficiaire->getId())
            ->andWhere("d.nom = '".$this->sanitize($name)."'");

        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();
        if ($user->isMembre()) {
            $qb->andWhere('d.bPrive = false');
        }

        return $qb
            ->getQuery()
            ->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
            ->getResult();
    }

    /**
     * @param Evenement|Contact|Note|Document $donneePersonnelle
     */
    public function addCreatorCentre($donneePersonnelle): void
    {
        $user = $this->tokenStorage->getToken()->getUser();
        if (!$user instanceof User) {
            return;
        }
        if (null === $donneePersonnelle->getId() && !$user->isAdministrateur() && !$user->isBeneficiaire()) {
            $beneficiaire = $donneePersonnelle->getBeneficiaire();

            $centre = false;

            foreach ($beneficiaire->getCentres() as $centre) {
                $centreCommun = $user->getCentres()->filter(static function (Centre $element) use ($centre) {
                    return $element === $centre;
                })->first();
                if (false !== $centreCommun) {
                    $centre = $centreCommun;
                    break;
                }
            }

            if (false !== $centre) {
                $creator = new CreatorCentre();
                $creator->setEntity($centre);
                $donneePersonnelle->addCreator($creator);
            }
        }
    }

    /**
     * @param Evenement|Contact|Note|Document $donneePersonnelle
     */
    public function addCreatorUser($donneePersonnelle): void
    {
        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();
        if (null !== $user && null === $donneePersonnelle->getId() && !$user->isAdministrateur()) {
            $creatorUser = new CreatorUser();
            $creatorUser->setEntity($user);

            $donneePersonnelle->addCreator($creatorUser);
        }
    }

    /**
     * @return JsonResponse
     */
    public function persist($id, $provider, $className, $request, $restManager, $beneficiaireProvider, $client): ?JsonResponse
    {
        try {
            $beneficiaire = $beneficiaireProvider->getEntity($id);

            $entity = new $className($beneficiaire);

            $provider->populate($entity, $request->request, $client);

            if (null !== $data = $restManager->getJsonValidationError($entity)) {
                return new JsonResponse($data, Response::HTTP_BAD_REQUEST);
            }

            $provider->save($entity);

            return new JsonResponse($entity, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @param Contact|Note|Evenement|Document|Dossier $entity
     */
    public function populate($entity, Client $client): void
    {
        $bPrive = filter_var($this->request->get('b_prive'), FILTER_VALIDATE_BOOLEAN);
        $entity
            ->setNom($this->request->get('nom'))
            ->setBPrive($bPrive);

        $this->addCreatorClient($entity);
    }

    public function addCreatorClient(DonneePersonnelle $entity): void
    {
        if (!($entity instanceof Dossier) && null === $entity->getId()) {
            if ($oldClient = $this->apiClientManager->getCurrentOldClient()) {
                $entity->addCreator((new CreatorClient())->setEntity($oldClient));
            }
        }
    }
}
