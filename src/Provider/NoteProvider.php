<?php

namespace App\Provider;

use App\Entity\Client;
use App\Entity\Note;
use App\Form\Type\NoteSimpleType;
use App\Form\Type\NoteType;
use App\Security\Authorization\Voter\DonneePersonnelleVoter;
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
    public function getEntity($id, $accessAttribute = null): Note
    {
        if (!$entity = $this->em->find(Note::class, $id)) {
            throw new NotFoundHttpException('No note found for id '.$id);
        }

        $attributes = [DonneePersonnelleVoter::DONNEEPERSONNELLE_VIEW, $accessAttribute];

        foreach ($attributes as $attribute) {
            if ($attribute && !$this->authorizationChecker->isGranted($attribute, $entity)) {
                throw new AccessDeniedException();
            }
        }

        return $entity;
    }

    /**
     * @param Note $entity
     */
    public function populate($entity, Client $client): void
    {
        parent::populate($entity, $client);

        $entity->setContenu($this->request->get('contenu'));
    }
}
