<?php

namespace App\ControllerV2;

use App\Entity\Attributes\Beneficiaire;
use App\Entity\Attributes\BeneficiaryCreationProcess;
use App\FormV2\UserCreation\CreateBeneficiaryType;
use App\ManagerV2\BeneficiaryCreationManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_MEMBRE')]
#[Route(path: '/beneficiary/create')]
class BeneficiaryCreationController extends AbstractController
{
    #[Route(path: '', name: 'create_beneficiary_home', methods: ['GET'])]
    public function createBeneficiaryHome(): Response
    {
        return $this->render('v2/user_creation/beneficiary/create_beneficiary.html.twig');
    }

    #[Route(
        path: '/{step}/{id?}',
        name: 'create_beneficiary',
        requirements: ['step' => '[0-6]', 'id' => '\d+'],
        methods: ['GET', 'POST'],
    )]
    public function createBeneficiary(
        Request $request,
        int $step,
        BeneficiaryCreationManager $manager,
        ?BeneficiaryCreationProcess $creationProcess,
    ): Response {
        if (0 === $step) {
            return $this->redirectToRoute('create_beneficiary_home');
        }
        $creationProcess = $manager->getOrCreate($creationProcess, $request->query->getBoolean('remotely'), $step);

        if (!$creationProcess->isCreating()) {
            return $this->redirectToRoute('list_beneficiaries');
        } elseif ($creationProcess->isLastStep()) {
            $manager->finishCreation($creationProcess);

            return $this->redirectToRoute('create_beneficiary_download_terms_of_use', ['id' => $creationProcess->getId()]);
        }

        $beneficiary = $creationProcess->getBeneficiary();
        $form = !$creationProcess->isStepWithForm() ? null : $this->createStepForm($beneficiary, $creationProcess)?->handleRequest($request);
        if ($form?->isSubmitted() && $form->isValid()) {
            $manager->createOrUpdate($creationProcess);

            return $this->redirectToRoute('create_beneficiary', ['step' => $creationProcess->getNextUnfilledStep(), 'id' => $creationProcess->getId()]);
        }

        return $this->render('v2/user_creation/beneficiary/create_beneficiary_step.html.twig', [
            'form' => $form,
            'beneficiaryCreationProcess' => $creationProcess,
            'beneficiary' => $beneficiary,
            'relays' => $this->getUser()?->getValidRelays(),
        ]);
    }

    #[Route(
        path: '/download-terms-of-use/{id<\d+>}',
        name: 'create_beneficiary_download_terms_of_use',
        methods: ['GET'],
    )]
    public function downloadTermsOfUse(BeneficiaryCreationProcess $beneficiaryCreationProcess): Response
    {
        $this->addFlash('success', 'beneficiary_created_successfully');

        return $this->render('v2/user_creation/beneficiary/download_terms_of_use.html.twig', [
            'beneficiaryCreationProcess' => $beneficiaryCreationProcess,
        ]);
    }

    #[Route(path: '/abort/{id<\d+>}', name: 'create_beneficiary_abort', methods: ['GET'])]
    public function abortCreation(BeneficiaryCreationProcess $beneficiaryCreationProcess, EntityManagerInterface $em): Response
    {
        $beneficiary = $beneficiaryCreationProcess->getBeneficiary();

        if ($beneficiaryCreationProcess->isCreating() && $beneficiary) {
            $em->remove($beneficiary);
            $em->flush();
            $this->addFlash('success', 'beneficiary_creation_canceled');
        }

        return $this->redirectToRoute('list_beneficiaries');
    }

    public function createStepForm(?Beneficiaire $beneficiary, BeneficiaryCreationProcess $creationProcess): ?FormInterface
    {
        return $this->createForm(CreateBeneficiaryType::class, $beneficiary, [
            'action' => $this->generateUrl('create_beneficiary', [
                'step' => $creationProcess->getCurrentStep(),
                'id' => $creationProcess->getId(),
                'remotely' => $creationProcess->isRemotely(),
            ]),
            'validation_groups' => CreateBeneficiaryType::getStepValidationGroup($creationProcess),
        ]);
    }
}
