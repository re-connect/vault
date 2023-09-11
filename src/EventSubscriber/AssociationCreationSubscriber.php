<?php

namespace App\EventSubscriber;

use App\Entity\Association;
use App\Entity\Centre;
use App\Entity\User;
use App\ManagerV2\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class AssociationCreationSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly UserManager $userManager)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::POST_SUBMIT => 'onPostSubmit',
        ];
    }

    public function onPostSubmit(FormEvent $event): void
    {
        $data = $event->getData();
        $form = $event->getForm();
        if (!$data instanceof Centre || $data->getId()) {
            return;
        }

        $association = $form->get('association')->getData();
        $newAssociation = $form->get('newAssociation')->getData();

        if (!$association && !$newAssociation) {
            $form->get('association')->addError(
                new FormError('Vous devez choisir une association existante, ou renseigner le nom de la nouvelle association'),
            );
        } else {
            $data->setAssociation($association ?? $this->createUserAssociation($newAssociation, $data->getTest())->getSubjectAssociation());

            $this->em->flush();
        }
    }

    private function createUserAssociation(string $associationName, bool $isTest): User
    {
        $association = (new Association())->setNom($associationName);

        $userAssociation = (new User())
            ->setPlainPassword($this->userManager->getRandomPassword())
            ->setNom($associationName)
            ->setTest($isTest)
            ->setSubjectAssociation($association)
            ->disable();

        $association->setUser($userAssociation);
        $this->em->persist($userAssociation);
        $this->em->persist($association);
        $this->userManager->updatePasswordWithPlain($userAssociation);

        return $userAssociation;
    }
}
