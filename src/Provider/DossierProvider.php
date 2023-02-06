<?php

namespace App\Provider;

use App\Entity\Beneficiaire;
use App\Entity\Document;
use App\Entity\DonneePersonnelle;
use App\Entity\Dossier;
use App\Event\DonneePersonnelleEvent;
use App\Event\REEvent;
use App\Form\Type\DossierSimpleType;
use App\Form\Type\DossierType;
use App\Security\Authorization\Voter\BeneficiaireVoter;
use App\Security\Authorization\Voter\DonneePersonnelleVoter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Exception\ValidatorException;

class DossierProvider extends DonneePersonnelleProvider
{
    protected string $entityName = Dossier::class;
    protected string $formType = DossierType::class;
    protected string $formSimpleType = DossierSimpleType::class;

    public function createFolder(Beneficiaire $beneficiaire, string $nom = 'Sans nom', bool $log = true): Dossier
    {
        if (!$this->authorizationChecker->isGranted(BeneficiaireVoter::GESTION_BENEFICIAIRE, $beneficiaire)) {
            throw new AccessDeniedException($this->translator->trans('donneePersonnelle.cantDisplay'));
        }

        $dossier = (new Dossier())
            ->setBeneficiaire($beneficiaire)
            ->setNom($nom);

        if (null !== $this->request && $dossierParentId = $this->request->get('dossier-parent')) {
            $dossierParent = $this->getEntity($dossierParentId);
            if ($dossier->getId() !== $dossierParent->getId()
                && $beneficiaire->getId() === $dossierParent->getBeneficiaire()->getId()) {
                $dossier->setDossierParent($dossierParent);
            }
        }

        $this->em->persist($dossier);
        $this->em->flush();

        if ($log) {
            $this->eventDispatcher->dispatch(new DonneePersonnelleEvent($dossier, $beneficiaire->getUser(), DonneePersonnelleEvent::DONNEEPERSONNELLE_CREATED), REEvent::RE_EVENT_DONNEEPERSONNELLE);
        }

        return $dossier;
    }

    public function getEntity($id, $accessAttribute = null): Dossier
    {
        /** @var Dossier $entity */
        if (!$entity = $this->em->getRepository(Dossier::class)->find($id)) {
            throw new NotFoundHttpException('No folder found for id '.$id);
        }

        $attributes = [DonneePersonnelleVoter::DONNEEPERSONNELLE_VIEW];

        foreach ($attributes as $attribute) {
            if ($attribute && !$this->authorizationChecker->isGranted($attribute, $entity)) {
                throw new AccessDeniedException();
            }
        }

        return $entity;
    }

    public function create(Beneficiaire $beneficiaire): Dossier
    {
        return (new Dossier())
            ->setBeneficiaire($beneficiaire)
            ->setNom('Sans nom');
    }

    public function moveDocumentInside(Document $document, Dossier $dossier): int
    {
        if ($document->getBeneficiaire() !== $dossier->getBeneficiaire()) {
            throw new \RuntimeException("Le document et le dossier n'appartiennent pas au même bénéficiaire");
        }
        if (!$this->authorizationChecker->isGranted(DonneePersonnelleVoter::DONNEEPERSONNELLE_EDIT, $dossier)) {
            throw new AccessDeniedException($this->translator->trans('donneePersonnelle.cantEdit'));
        }

        $document
            ->setBPrive($dossier->getBPrive())
            ->setDossier($dossier);
        $this->em->persist($document);
        $this->em->flush();

        $this->eventDispatcher->dispatch(new DonneePersonnelleEvent($dossier, $document->getBeneficiaire()->getUser(), DonneePersonnelleEvent::DONNEEPERSONNELLE_MODIFIED), REEvent::RE_EVENT_DONNEEPERSONNELLE);

        return $dossier->getId();
    }

    public function delete(DonneePersonnelle $donneePersonnelle, bool $log = true): void
    {
        /** @var Dossier $donneePersonnelle */
        $dossierParent = $donneePersonnelle->getDossierParent();
        foreach ($donneePersonnelle->getDocuments() as $document) {
            $document->setDossier($dossierParent);
            $this->em->persist($document);
        }

        foreach ($donneePersonnelle->getSousDossiers() as $sousDossier) {
            $sousDossier->setDossierParent($dossierParent);
            $this->em->persist($sousDossier);
        }

        parent::delete($donneePersonnelle, $log);
    }

    public function changePrive(DonneePersonnelle $donneePersonnelle, $bPrive = true): void
    {
        if (!$this->authorizationChecker->isGranted(DonneePersonnelleVoter::DONNEEPERSONNELLE_EDIT, $donneePersonnelle)) {
            throw new AccessDeniedException($this->translator->trans('donneePersonnelle.cantEdit'));
        }

        $this->changePriveRecursive($donneePersonnelle, $bPrive);

        parent::changePrive($donneePersonnelle, $bPrive);
    }

    private function changePriveRecursive(DonneePersonnelle $dossier, $bPrive = true): void
    {
        /** @var Dossier $dossier */
        foreach ($dossier->getDocuments() as $document) {
            $document->setBPrive($bPrive && !$document->getBPrive());
            $this->em->persist($document);
        }

        /** @var Dossier $sousDossier */
        foreach ($dossier->getSousDossiers() as $sousDossier) {
            $sousDossier->setBPrive($bPrive && !$sousDossier->getBPrive());
            $this->em->persist($sousDossier);

            $this->changePriveRecursive($sousDossier, $bPrive);
        }
    }

    public function setNom(Dossier $entity, Request $request): void
    {
        if (!$this->authorizationChecker->isGranted(DonneePersonnelleVoter::DONNEEPERSONNELLE_EDIT, $entity)) {
            throw new AccessDeniedException("Vous n'avez pas le droit de modifier ce dossier");
        }

        if (null === $name = $request->request->get('name')) {
            throw new ValidatorException('Name missing.');
        }

        $entity->setNom($name);

        $this->em->flush();
    }

    public function moveDossierInside(Dossier $dossier, Dossier $dossierDestinataire): void
    {
        if ($dossier->getBeneficiaire()->getId() !== $dossierDestinataire->getBeneficiaire()->getId()) {
            throw new BadRequestHttpException($this->translator->trans('folders.notSameBeneficiaire'));
        }
        if ($this->isInFolder($dossier, $dossierDestinataire)) {
            throw new BadRequestHttpException($this->translator->trans('folder.isSubFolder'));
        }
        if (!$this->authorizationChecker->isGranted(DonneePersonnelleVoter::DONNEEPERSONNELLE_EDIT, $dossier)) {
            throw new AccessDeniedException($this->translator->trans('donneePersonnelle.cantEdit'));
        }

        $dossier
            ->setBPrive($dossierDestinataire->getBPrive())
            ->setDossierParent($dossierDestinataire);
        $this->em->persist($dossier);
        $this->em->flush();

        $this->eventDispatcher->dispatch(new DonneePersonnelleEvent($dossier, $dossierDestinataire->getBeneficiaire()->getUser(), DonneePersonnelleEvent::DONNEEPERSONNELLE_MODIFIED), REEvent::RE_EVENT_DONNEEPERSONNELLE);
    }

    private function isInFolder(Dossier $dossier, Dossier $dossierDestinataire): bool
    {
        foreach ($dossier->getSousDossiers() as $sousDossier) {
            if ($sousDossier->getId() === $dossierDestinataire->getId() || $this->isInFolder($sousDossier, $dossierDestinataire)) {
                return true;
            }
        }

        return false;
    }

    public function getOutFromFolder(Dossier $dossier): void
    {
        if (!$this->authorizationChecker->isGranted(DonneePersonnelleVoter::DONNEEPERSONNELLE_EDIT, $dossier)) {
            throw new AccessDeniedException($this->translator->trans('donneePersonnelle.cantEdit'));
        }

        $dossier->setDossierParent();
        $this->em->flush();

        $this->eventDispatcher->dispatch(
            new DonneePersonnelleEvent(
                $dossier,
                $dossier->getBeneficiaire()->getUser(),
                DonneePersonnelleEvent::DONNEEPERSONNELLE_MODIFIED
            ),
            REEvent::RE_EVENT_DONNEEPERSONNELLE,
        );
    }
}
