<?php

namespace App\Controller;

use App\Entity\Annexe;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Annotation\Route;

class AnnexeController extends AbstractController
{
    /**
     * @Route("/annexe/{url}", name="re_admin_annexe")
     */
    public function show(EntityManagerInterface $em, $url): BinaryFileResponse
    {
        /** @var Annexe $annexe */
        $annexe = $em->getRepository(Annexe::class)->findOneByUrl($url);

        return new BinaryFileResponse($annexe);
    }
}
