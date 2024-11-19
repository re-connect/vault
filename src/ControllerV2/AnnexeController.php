<?php

namespace App\ControllerV2;

use App\Entity\Attributes\Annexe;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Attribute\Route;

class AnnexeController extends AbstractController
{
    #[Route(path: '/annexe/{url}', name: 're_admin_annexe')]
    public function show(EntityManagerInterface $em, string $url): BinaryFileResponse
    {
        /** @var Annexe $annexe */
        $annexe = $em->getRepository(Annexe::class)->findOneBy(['url' => $url]);

        return new BinaryFileResponse($annexe);
    }
}
