<?php

namespace App\Provider;

use App\Api\Manager\ApiClientManager;
use App\Entity\Membre;
use App\Entity\User;
use App\Event\MembreEvent;
use App\Event\REEvent;
use App\Manager\UserManager;
use App\Security\Authorization\Voter\MembreVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class MembreProvider
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly UserManager $userManager,
        private readonly RequestStack $requestStack,
        private readonly ApiClientManager $apiClientManager,
    ) {
    }

    public function save(Membre $membre, User $user): void
    {
        $bIsCreating = false;
        if (null === $membre->getId()) {
            $bIsCreating = true;
        }

        $this->userManager->updateUser($membre->getUser(), false);
        $this->em->persist($membre);
        $this->em->flush();

        $eventType = $bIsCreating ? MembreEvent::MEMBRE_CREATED : MembreEvent::MEMBRE_MODIFIED;
        $this->eventDispatcher->dispatch(new MembreEvent($membre, $eventType, $user), REEvent::RE_EVENT_MEMBRE);
    }

    public function getEntityByDistantId($distantId, $secured = true): Membre
    {
        $oldClient = $this->apiClientManager->getCurrentOldClient();
        if (!$entity = $this->em->getRepository(Membre::class)->findByDistantId($distantId, $oldClient->getRandomId())) {
            throw new NotFoundHttpException('No member found for distant id '.$distantId);
        }

        if ($secured && false === $this->authorizationChecker->isGranted(MembreVoter::GESTION_MEMBRE, $entity)) {
            throw new AccessDeniedException();
        }

        return $entity;
    }

    /**
     * @throws \Exception
     */
    public function populate(Membre $entity)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException();
        }
        $entity
            ->getUser()
            ->setPrenom($request->get('prenom'))
            ->setNom($request->get('nom'))
            ->setTelephone($request->get('telephone'))
            ->setEmail($request->get('email'));
    }

    public function getEntity($id): Membre
    {
        if (!$entity = $this->em->find(Membre::class, $id)) {
            throw new NotFoundHttpException('No member found for id '.$id);
        }

        if (false === $this->authorizationChecker->isGranted(MembreVoter::GESTION_MEMBRE, $entity)) {
            throw new AccessDeniedException();
        }

        return $entity;
    }
}
