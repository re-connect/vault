<?php

namespace App\Controller;

use App\Entity\Document;
use App\Manager\ConsultationBeneficiaireManager;
use App\Manager\DocumentManager;
use App\Provider\BeneficiaireProvider;
use App\Provider\DocumentProvider;
use App\Security\Authorization\Voter\DonneePersonnelleVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/appli", name="re_app_document_", options={"expose"=true})
 */
class DocumentController extends AbstractController
{
    /**
     * @Route("/beneficiaire/{id}/document",
     *     name="list",
     *     requirements={"id"="\d{1,10}"}
     * )
     */
    public function index(
        $id,
        DocumentProvider $provider,
        BeneficiaireProvider $beneficiaireProvider,
        ConsultationBeneficiaireManager $consultationBeneficiaireManager,
        EntityManagerInterface $em
    ): Response {
        $beneficiaire = $beneficiaireProvider->getEntity($id);
        /* Enregistrement du premier accès aux documents du bénéficiaire par le bénéficiaire */
        if ($beneficiaire->hasNeverClickedMesDocuments() && $beneficiaire->getUser()->getId() === $this->getUser()->getId()) {
            $beneficiaire->setNeverClickedMesDocuments(false);
            $em->persist($beneficiaire);
            $em->flush();
        }
        $consultationBeneficiaireManager->handleUserVisit($beneficiaire);

        return $this->render('app\document\list.html.twig', [
            'beneficiaire' => $beneficiaire,
            'maxSizeForBeneficiaire' => $provider->getMaxSizeForBeneficiaire(),
        ]);
    }

    public function listByDistantId($distantId, BeneficiaireProvider $beneficiaireProvider): RedirectResponse
    {
        $beneficiaire = $beneficiaireProvider->getEntityByDistantId($distantId);

        return $this->redirectToRoute('re_app_document_list', ['id' => $beneficiaire->getId()]);
    }

    public function telecharger(
        Document $document,
        DocumentManager $documentManager,
        AuthorizationCheckerInterface $authorizationChecker
    ): Response {
        if (false === $authorizationChecker->isGranted(DonneePersonnelleVoter::DONNEEPERSONNELLE_VIEW, $document)) {
            throw new AccessDeniedException("Vous n'avez pas le droit d'afficher les documents de ce beneficiaire");
        }
        $key = $document->getObjectKey();
        $filepath = $documentManager->getPresignedUrl($key);
        $content = file_get_contents($filepath);
        $response = new Response();
        // set headers
        $response->headers->set('Content-Type', 'mime/type');
        $response->headers->set('Content-Disposition', 'attachment;filename="'.$document->getNom());
        $response->setContent($content);

        return $response;
    }
}
