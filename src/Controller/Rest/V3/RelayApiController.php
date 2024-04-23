<?php

namespace App\Controller\Rest\V3;

use App\ControllerV2\AbstractController;
use App\Entity\Centre;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/api/v3/centers', format: 'json')]
#[IsGranted('ROLE_USER')]
class RelayApiController extends AbstractController
{
    #[Route(path: '/{id<\d+>}/accept', methods: ['PATCH'])]
    public function accept(Centre $relay, EntityManagerInterface $em): JsonResponse
    {
        $userRelay = $this->getUser()->getUserRelay($relay)?->setBValid(true);
        $em->flush();

        return $this->json($userRelay);
    }

    #[Route(path: '/{id<\d+>}/leave', methods: ['PATCH'])]
    public function leave(Centre $relay, EntityManagerInterface $em): JsonResponse
    {
        $userRelay = $this->getUser()->getUserRelay($relay);
        $em->remove($userRelay);
        $em->flush();

        return $this->json(['success' => 'OK']);
    }
}
