<?php

namespace App\Provider;

use App\Entity\Gestionnaire;
use App\Entity\User;
use App\Event\GestionnaireEvent;
use App\Event\REEvent;
use App\Manager\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class GestionnaireProvider
{
    private EntityManagerInterface $em;
    private UserManager $userManager;
    private RequestStack $requestStack;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        EntityManagerInterface $em,
        EventDispatcherInterface $eventDispatcher,
        UserManager $userManager,
        RequestStack $requestStack,
    ) {
        $this->em = $em;
        $this->eventDispatcher = $eventDispatcher;
        $this->userManager = $userManager;
        $this->requestStack = $requestStack;
    }

    public function save(Gestionnaire $entity, User $user): void
    {
        $bIsCreating = null === $entity->getId();

        $this->userManager->updateUser($entity->getUser(), false);
        $this->em->persist($entity);
        $this->em->flush();

        $type = true === $bIsCreating ? GestionnaireEvent::GESTIONNAIRE_CREATED : GestionnaireEvent::GESTIONNAIRE_MODIFIED;
        $this->eventDispatcher->dispatch(new GestionnaireEvent($entity, $type, $user), REEvent::RE_EVENT_GESTIONNAIRE);
    }

    public function populate(Gestionnaire $entity): void
    {
        if (null === $request = $this->requestStack->getCurrentRequest()) {
            throw new BadRequestHttpException();
        }
        $entity
            ->getUser()
            ->setPrenom($request->get('prenom'))
            ->setNom($request->get('nom'))
            ->setTelephoneFixe($request->get('telephone'))
            ->setEmail($request->get('email'));
    }
}
