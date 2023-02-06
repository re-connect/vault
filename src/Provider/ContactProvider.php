<?php

namespace App\Provider;

use App\Entity\Client;
use App\Entity\Contact;
use App\Form\Type\ContactSimpleType;
use App\Form\Type\ContactType;
use App\Security\Authorization\Voter\DonneePersonnelleVoter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ContactProvider extends DonneePersonnelleProvider
{
    protected string $entityName = Contact::class;
    protected string $formType = ContactType::class;
    protected string $formSimpleType = ContactSimpleType::class;

    /**
     * @param string|null $accessAttribute
     */
    public function getEntity($id, $accessAttribute = null): Contact
    {
        if (!$entity = $this->em->find(Contact::class, $id)) {
            throw new NotFoundHttpException('No contact found for id '.$id);
        }

        $attributes = [DonneePersonnelleVoter::DONNEEPERSONNELLE_VIEW, $accessAttribute];

        foreach ($attributes as $attribute) {
            if ($attribute && !$this->authorizationChecker->isGranted($attribute, $entity)) {
                throw new AccessDeniedException();
            }
        }

        return $entity;
    }

    public function populate($entity, Client $client): void
    {
        parent::populate($entity, $client);

        $entity
            ->setTelephone($this->request->get('telephone'))
            ->setAssociation($this->request->get('association'))
            ->setCommentaire($this->request->get('commentaire'))
            ->setEmail($this->request->get('email'))
            ->setPrenom($this->request->get('prenom'));
    }
}
