<?php

namespace App\ControllerV2;

use App\Api\Manager\ApiClientManager;
use App\Entity\Beneficiaire;
use App\Repository\BeneficiaireRepository;
use App\ServiceV2\RosalieService;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

class RosalieInteroperabilityController extends AbstractController
{
    #[IsGranted('ROLE_MEMBRE')]
    #[Route('/beneficiaries/{id}/add-si-siao-number', name: 'add_si_siao_number')]
    public function addSiSiaoNumber(Request $request, Beneficiaire $beneficiary, RosalieService $service, TranslatorInterface $translator): Response
    {
        $form = $this->createFormBuilder()
            ->add('number', TextType::class, ['label' => 'si_siao_number'])
            ->getForm()->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $beneficiary->setSiSiaoNumber($form->get('number')->getData());
            if ($service->beneficiaryExistsOnRosalie($beneficiary)) {
                $service->linkBeneficiaryToRosalie($beneficiary);
                $this->addFlash('success', $translator->trans('si_siao_number_found_rosalie'));
            }

            return $this->redirectToRoute('list_beneficiaries');
        }

        return $this->render('v2/rosalie/add_si_siao_number.html.twig', [
            'beneficiary' => $beneficiary,
            'form' => $form,
        ]);
    }

    #[IsGranted('ROLE_OAUTH2_SI_SIAO_NUMBERS')]
    #[Route('/api/v3/get-si-siao-numbers', name: 'get_si_siao_numbers', methods: ['GET'])]
    public function getSiSiaoNumbers(BeneficiaireRepository $repository, ApiClientManager $apiClientManager): JsonResponse
    {
        return $this->json($repository->getBeneficiariesSiSiaoNumbers($apiClientManager->getCurrentOldClient()));
    }
}
