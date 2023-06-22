<?php

namespace App\Controller\Api;

use App\Entity\Beneficiaire;
use App\Provider\DocumentProvider;
use App\Repository\DocumentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class DocumentApiController extends AbstractController
{
    /**
     * @Route("/beneficiaries/{id}/documents","list_beneficiary_documents",methods={"GET"})
     */
    public function getBeneficiaryDocuments(Beneficiaire $beneficiaire, DocumentRepository $repository, DocumentProvider $provider): JsonResponse
    {
        $documents = $repository->findBy(['beneficiaire' => $beneficiaire]);
        $provider->hydrateDocumentsWithUris($documents);

        return $this->json($documents, 200, [], ['groups' => ['document:read']]);
    }
}
