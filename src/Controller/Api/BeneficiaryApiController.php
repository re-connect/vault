<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Provider\CentreProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class BeneficiaryApiController extends AbstractController
{
    #[Route(path: '/beneficiaries/mine', name: 'list_my_beneficiaries', methods: ['GET'])]
    public function getMine(CentreProvider $centreProvider): JsonResponse
    {
        $user = $this->getUser();
        $member = $user instanceof User ? $user->getSubjectMembre() : null;

        return $this->json($member ? $centreProvider->getBeneficiairesFromMembre($member) : []);
    }
}
