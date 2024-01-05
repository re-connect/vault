<?php

namespace App\ControllerV2;

use App\Api\Manager\ApiClientManager;
use App\Entity\Beneficiaire;
use App\Entity\Membre;
use App\Repository\BeneficiaireRepository;
use App\Security\VoterV2\ProVoter;
use App\ServiceV2\RosalieService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class RosalieInteroperabilityController extends AbstractController
{
    public function __construct(
        private readonly RosalieService $service,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[IsGranted('ROLE_MEMBRE')]
    #[Route('/beneficiaries/{id}/add-si-siao-number', name: 'add_si_siao_number')]
    public function addSiSiaoNumber(Request $request, Beneficiaire $beneficiary): Response
    {
        $beneficiaryCreationProcess = $beneficiary->getCreationProcess();

        $redirection = $beneficiaryCreationProcess?->getIsCreating()
            ? $this->redirectToRoute('create_beneficiary', ['id' => $beneficiaryCreationProcess->getId(), 'step' => $beneficiaryCreationProcess->getLastReachedStep()])
            : $this->redirectToRoute('affiliate_beneficiary_relays', ['id' => $beneficiary->getId()]);

        return $this->processSiSiaoNumberForm($request, $beneficiary, $redirection);
    }

    private function processSiSiaoNumberForm(Request $request, Beneficiaire $beneficiary, RedirectResponse $redirection): Response
    {
        $form = $this->createFormBuilder()
            ->add('number', TextType::class, ['label' => 'si_siao_number', 'data' => $beneficiary->getSiSiaoNumber()])
            ->getForm()->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('v2/rosalie/add_si_siao_number.html.twig', [
                'beneficiary' => $beneficiary,
                'form' => $form,
            ]);
        }

        $beneficiary->setSiSiaoNumber($form->get('number')->getData());
        $this->em->flush();
        if ($this->getUser()->usesRosalie()) {
            $this->createRosalieLink($beneficiary);
        }

        return $redirection;
    }

    #[IsGranted('ROLE_OAUTH2_SI_SIAO_NUMBERS')]
    #[Route('/api/v3/get-si-siao-numbers', name: 'get_si_siao_numbers', methods: ['GET'])]
    public function getSiSiaoNumbers(BeneficiaireRepository $repository, ApiClientManager $apiClientManager): JsonResponse
    {
        return $this->json($repository->getBeneficiariesSiSiaoNumbers($apiClientManager->getCurrentOldClient()));
    }

    #[IsGranted(ProVoter::MANAGE)]
    #[Route(path: 'pro/rosalie/{id}', name: 'rosalie_pro', methods: ['GET', 'POST'])]
    public function activateRosalie(Request $request, Membre $member, EntityManagerInterface $em): Response
    {
        if (!$this->getUser()?->usesRosalie()) {
            return $this->redirectToRoute('list_pro');
        }
        $form = $this->createFormBuilder()
            ->add('usesRosalie', CheckboxType::class, ['label' => 'rosalie', 'required' => false, 'value' => $member->usesRosalie()])
            ->setAction($this->generateUrl('rosalie_pro', ['id' => $member->getId()]))
            ->getForm()
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $member->setUsesRosalie($form->getData()['usesRosalie']);
            $em->flush();

            return $this->redirectToRoute('list_pro');
        }

        return $this->render('v2/pro/rosalie/rosalie.html.twig', ['form' => $form, 'user' => $member->getUser()]);
    }

    private function createRosalieLink(Beneficiaire $beneficiary): void
    {
        $beneficiaryCheck = $this->service->checkBeneficiaryOnRosalie($beneficiary);

        if ($beneficiaryCheck->beneficiaryIsFound()) {
            if ($this->service->linkBeneficiaryToRosalie($beneficiaryCheck->getBeneficiary())) {
                $this->addFlash('success', 'si_siao_number_found_rosalie');
            } else {
                $this->addFlash('error', 'si_siao_number_already_linked');
            }
        } else {
            $this->addFlash('error', $beneficiaryCheck->getSamuSocialErrorMessage());
        }
    }

    #[IsGranted('ROLE_MEMBRE')]
    #[Route('/beneficiaries/{id}/link-rosalie', name: 'link_rosalie')]
    public function linkToRosalie(Beneficiaire $beneficiary): Response
    {
        $this->createRosalieLink($beneficiary);
        $beneficiaryCreationProcess = $beneficiary->getCreationProcess();

        return $beneficiaryCreationProcess?->getIsCreating()
            ? $this->redirectToRoute('create_beneficiary', ['id' => $beneficiaryCreationProcess->getId(), 'step' => $beneficiaryCreationProcess->getLastReachedStep()])
            : $this->redirectToRoute('affiliate_beneficiary_relays', ['id' => $beneficiary->getId()]);
    }
}
