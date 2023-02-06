<?php

namespace App\ControllerV2;

use App\Entity\Attributes\BeneficiaryCreationProcess;
use App\Entity\Beneficiaire;
use App\FormV2\UserCreation\CreateBeneficiaryType;
use App\ManagerV2\BeneficiaryCreationManager;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[IsGranted('ROLE_MEMBRE')]
class BeneficiaryCreationController extends AbstractController
{
    #[Route(path: '/beneficiary/create', name: 'create_beneficiary_home', methods: ['GET'])]
    public function createBeneficiaryHome(): Response
    {
        return $this->renderForm('v2/user_creation/beneficiary/create_beneficiary.html.twig');
    }

    #[Route(
        path: 'beneficiary/create/{step}/{id?}',
        name: 'create_beneficiary',
        requirements: ['step' => '[1-6]', 'id' => '\d+'],
        methods: ['GET', 'POST'],
    )]
    public function createBeneficiary(
        Request $request,
        int $step,
        ?BeneficiaryCreationProcess $beneficiaryCreationProcess,
        BeneficiaryCreationManager $manager,
    ): Response {
        if ($beneficiaryCreationProcess && !$beneficiaryCreationProcess->getIsCreating()) {
            return $this->redirectToRoute('re_membre_beneficiaires');
        }
        $beneficiaryCreationProcess = $beneficiaryCreationProcess
            ?? (new BeneficiaryCreationProcess())
                ->setIsCreating(true)
                ->setBeneficiary((new Beneficiaire())
                ->setCreePar($this->getUser()));

        if ($request->query->getBoolean('remotely') && 1 === $step) {
            $beneficiaryCreationProcess->setRemotely(true);
        }

        $beneficiary = $beneficiaryCreationProcess->getBeneficiary();
        $remotely = $beneficiaryCreationProcess->isRemotely();
        $isLastStep = $beneficiaryCreationProcess->getTotalSteps() === $step;

        if ($isLastStep) {
            $manager->finishCreation($beneficiaryCreationProcess);

            return $this->redirectToRoute('create_beneficiary_download_terms_of_use', ['id' => $beneficiaryCreationProcess->getId()]);
        }

        $isFormStep = $beneficiaryCreationProcess->getTotalFormSteps() >= $step;
        $form = !$isFormStep ? null : $this->createForm(CreateBeneficiaryType::class, $beneficiary, [
            'action' => $this->generateUrl('create_beneficiary', ['step' => $step, 'id' => $beneficiaryCreationProcess->getId(), 'remotely' => $remotely]),
            'validation_groups' => ['beneficiaire', ...$manager->getStepValidationGroup($remotely, $step)],
            'step' => $step,
        ])->handleRequest($request);

        if ($form && $form->isSubmitted() && $form->isValid()) {
            $manager->createOrUpdate($beneficiaryCreationProcess);

            return $this->redirectToRoute('create_beneficiary', ['step' => $step + 1, 'id' => $beneficiaryCreationProcess->getId()]);
        }

        return $this->renderForm('v2/user_creation/beneficiary/create_beneficiary_step.html.twig', [
            'form' => $form,
            'beneficiaryCreationProcess' => $beneficiaryCreationProcess,
            'stepTitle' => $manager->getStepTitle($step),
            'beneficiary' => $beneficiary,
            'step' => $step,
        ]);
    }

    #[Route(
        path: 'beneficiary/create/download-terms-of-use/{id<\d+>}',
        name: 'create_beneficiary_download_terms_of_use',
        methods: ['GET'],
    )]
    public function downloadTermsOfUse(BeneficiaryCreationProcess $beneficiaryCreationProcess): Response
    {
        $this->addFlash('success', 'beneficiary_created_successfully');

        return $this->render('v2/user_creation/beneficiary/_download_terms_of_use.html.twig', [
            'beneficiaryCreationProcess' => $beneficiaryCreationProcess,
        ]);
    }

    #[Route(path: 'beneficiary/create/abort/{id<\d+>}', name: 'create_beneficiary_abort', methods: ['GET'])]
    public function abortCreation(BeneficiaryCreationProcess $beneficiaryCreationProcess, EntityManagerInterface $em): Response
    {
        if ($beneficiaryCreationProcess->getIsCreating()) {
            $em->remove($beneficiaryCreationProcess->getBeneficiary());
            $em->flush();
            $this->addFlash('success', 'beneficiary_creation_canceled');
        }

        return $this->redirectToRoute('re_membre_beneficiaires');
    }
}
