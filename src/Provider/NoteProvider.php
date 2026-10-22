<?php

namespace App\Provider;

use App\Entity\Client;
use App\Entity\DonneePersonnelle;
use App\Entity\Note;
use App\Form\Type\NoteSimpleType;
use App\Form\Type\NoteType;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class NoteProvider extends DonneePersonnelleProvider
{
    protected string $entityName = Note::class;
    protected string $formType = NoteType::class;
    protected string $formSimpleType = NoteSimpleType::class;

    /**
     * @param string|null $accessAttribute
     */
    public function getEntity(string $id, $accessAttribute = null): Note
    {
        if (!$entity = $this->em->find(Note::class, $id)) {
            throw new NotFoundHttpException('No note found for id '.$id);
        }

        if (!$this->isAccessGranted($entity)) {
            throw new AccessDeniedException();
        }

        if ($accessAttribute && !$this->authorizationChecker->isGranted($accessAttribute, $entity)) {
            throw new AccessDeniedException();
        }

        return $entity;
    }

    /**
     * @param Note $entity
     */
    #[\Override]
    public function populate(DonneePersonnelle $entity, Client $client): void
    {
        parent::populate($entity, $client);

        $entity->setContenu($this->request->get('contenu'));
    }
}
