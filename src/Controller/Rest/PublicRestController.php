<?php

namespace App\Controller\Rest;

use App\Entity\Beneficiaire;
use App\Entity\Centre;
use App\Entity\Contact;
use App\Entity\Evenement;
use App\Entity\Membre;
use App\Entity\Note;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class PublicRestController extends AbstractController
{
    /**
     * @Route("/public/kpis", name="public_kpis", methods={"GET"})
     */
    public function index(EntityManagerInterface $em): JsonResponse
    {
        $beneficiaireRepository = $em->getRepository(Beneficiaire::class);
        $membreRepository = $em->getRepository(Membre::class);
        $contactRepository = $em->getRepository(Contact::class);
        $noteRepository = $em->getRepository(Note::class);
        $evenementRepository = $em->getRepository(Evenement::class);
        $centreRepository = $em->getRepository(Centre::class);

        return $this->json([
            'beneficiaires' => (int) $beneficiaireRepository->countKPI(),
            'membres' => (int) $membreRepository->countKPI(),
            'contacts' => $contactRepository->count([]),
            'notes' => $noteRepository->count([]),
            'evenements' => $evenementRepository->count([]),
            'centres' => $centreRepository->count(['test' => false]),
        ]);
    }
}
