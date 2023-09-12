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
use Symfony\Component\Form\FormInterface;

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

        if ($data->getId()) {
            return;
        }

        if ($data instanceof Centre) {
            $this->createUserAssociationFromRelay($data, $form);
        } elseif ($data instanceof Association) {
            $this->createUserAssociation($data);
        }

        $this->em->flush();
    }

    private function createUserAssociationFromRelay(Centre $data, FormInterface $form): void
    {
        $association = $form->get('association')->getData();
        $newAssociationName = $form->get('newAssociationName')->getData();

        if (!$association && !$newAssociationName) {
            $form->get('association')->addError(
                new FormError('Vous devez choisir une association existante, ou renseigner le nom de la nouvelle association'),
            );

            return;
        }

        $data->setAssociation($association ?? $this->createUserAssociation(
            (new Association())->setNom($newAssociationName),
            $data->getTest(),
        )->getSubjectAssociation());
    }

    private function createUserAssociation(Association $association, bool $isTest = false): User
    {
        $userAssociation = (new User())
            ->setPlainPassword($this->userManager->getRandomPassword())
            ->setNom($association->getNom())
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
