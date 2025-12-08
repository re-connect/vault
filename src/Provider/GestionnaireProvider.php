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
    public function __construct(private readonly EntityManagerInterface $em, private readonly EventDispatcherInterface $eventDispatcher, private readonly UserManager $userManager, private readonly RequestStack $requestStack)
    {
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
