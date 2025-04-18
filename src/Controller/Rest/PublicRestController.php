<?php

namespace App\Controller\Rest;

use App\Entity\Attributes\Beneficiaire;
use App\Entity\Attributes\Centre;
use App\Entity\Attributes\Contact;
use App\Entity\Attributes\Note;
use App\Entity\Evenement;
use App\Entity\Gestionnaire;
use App\Entity\Membre;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class PublicRestController extends AbstractController
{
    #[Route(path: '/public/kpis', name: 'public_kpis', methods: ['GET'])]
    public function index(EntityManagerInterface $em): JsonResponse
    {
        $beneficiaireRepository = $em->getRepository(Beneficiaire::class);
        $gestionnaireRepository = $em->getRepository(Gestionnaire::class);
        $membreRepository = $em->getRepository(Membre::class);
        $contactRepository = $em->getRepository(Contact::class);
        $noteRepository = $em->getRepository(Note::class);
        $evenementRepository = $em->getRepository(Evenement::class);
        $centreRepository = $em->getRepository(Centre::class);

        return $this->json([
            'beneficiaires' => (int) $beneficiaireRepository->countKPI(),
            'gestionnaires' => (int) $gestionnaireRepository->countKPI(),
            'membres' => (int) $membreRepository->countKPI(),
            'contacts' => $contactRepository->count([]),
            'notes' => $noteRepository->count([]),
            'evenements' => $evenementRepository->count([]),
            'centres' => $centreRepository->count(['test' => false]),
        ]);
    }
}
