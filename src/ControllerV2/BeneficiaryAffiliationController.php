<?php

namespace App\ControllerV2;

use App\Entity\Beneficiaire;
use App\Entity\Centre;
use App\FormV2\UserAffiliation\AffiliateBeneficiaryType;
use App\FormV2\UserAffiliation\DisaffiliateBeneficiaryType;
use App\FormV2\UserAffiliation\Model\AffiliateBeneficiaryFormModel;
use App\FormV2\UserAffiliation\Model\DisaffiliateBeneficiaryFormModel;
use App\FormV2\UserAffiliation\Model\SearchBeneficiaryFormModel;
use App\FormV2\UserAffiliation\SearchBeneficiaryType;
use App\ManagerV2\BeneficiaryAffiliationManager;
use App\ServiceV2\PaginatorService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_MEMBRE')]
class BeneficiaryAffiliationController extends AbstractController
{
    #[Route(path: '/beneficiary/affiliate', name: 'affiliate_beneficiary_home', methods: ['GET'])]
    public function home(): Response
    {
        return $this->render('v2/user_affiliation/beneficiary/affiliate_beneficiary.html.twig');
    }

    #[Route(path: '/beneficiary/affiliate/search', name: 'affiliate_beneficiary_search', methods: ['GET', 'POST'])]
    public function search(Request $request, BeneficiaryAffiliationManager $manager, PaginatorService $paginator): Response
    {
        $birthDate = $request->query->get('birthdate');
        $searchBeneficiaryModel = (new SearchBeneficiaryFormModel())
            ->setFirstname($request->query->get('firstname'))
            ->setLastname($request->query->get('lastname'))
            ->setBirthDate($birthDate ? new \DateTime($birthDate) : null);

        $searchForm = $this->createForm(SearchBeneficiaryType::class, $searchBeneficiaryModel, [
            'action' => $this->generateUrl('affiliate_beneficiary_search'),
        ])->handleRequest($request);

        $beneficiaries = $manager->getBeneficiariesFromFormModel($searchBeneficiaryModel);

        if ($searchForm->isSubmitted() && $searchForm->isValid()) {
            $beneficiaries = $manager->getBeneficiariesFromFormModel($searchBeneficiaryModel);
        }

        return $this->renderForm('v2/user_affiliation/beneficiary/affiliate_beneficiary_search.html.twig', [
            'form' => $searchForm,
            'beneficiaries' => $paginator->create(
                $beneficiaries,
                $request->query->getInt('page', 1),
            ),
            'search' => $searchBeneficiaryModel,
        ]);
    }

    #[Route(
        path: '/beneficiary/{id}/affiliate/relays',
        name: 'affiliate_beneficiary_relays',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST'],
    )]
    public function relays(
        Request $request,
        Beneficiaire $beneficiary,
        BeneficiaryAffiliationManager $manager,
        TranslatorInterface $translator,
    ): Response {
        $availableRelaysForAffiliation = $manager->getAvailableRelaysForAffiliation($this->getUser(), $beneficiary);

        if (0 === $availableRelaysForAffiliation->count()) {
            return $this->render('v2/user_affiliation/beneficiary/_no_relay_available.html.twig');
        }

        $affiliateBeneficiaryModel = (new AffiliateBeneficiaryFormModel())
            ->setRelays($availableRelaysForAffiliation);

        $form = $this->createForm(AffiliateBeneficiaryType::class, $affiliateBeneficiaryModel, [
            'action' => $this->generateUrl('affiliate_beneficiary_relays', ['id' => $beneficiary->getId()]),
            'beneficiary' => $beneficiary,
        ])->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $secretAnswer = $affiliateBeneficiaryModel->getSecretAnswer();
            $isSecretAnswerValid = $manager->isSecretAnswerValid($beneficiary, $secretAnswer);

            if (!$secretAnswer || $isSecretAnswerValid) {
                $manager->affiliateBeneficiary($beneficiary, $affiliateBeneficiaryModel->getRelays(), $isSecretAnswerValid);
                $this->addFlash('success', 'beneficiary_added_to_relays');

                return $this->redirectToRoute('re_membre_beneficiaires');
            }
            $form->get('secretAnswer')->addError(new FormError($translator->trans('wrong_secret_answer')));
        }

        return $this->renderForm('v2/user_affiliation/beneficiary/_relays_form.html.twig', [
            'beneficiary' => $beneficiary,
            'availableRelays' => $availableRelaysForAffiliation,
            'form' => $form,
            'formModel' => $affiliateBeneficiaryModel,
        ]);
    }

    #[Route(
        path: '/beneficiary/{id}/disaffiliate',
        name: 'disaffiliate_beneficiary',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('UPDATE', 'beneficiary')]
    public function disaffiliate(
        Request $request,
        Beneficiaire $beneficiary,
        BeneficiaryAffiliationManager $manager,
        TranslatorInterface $translator,
    ): Response {
        $professional = $this->getProfessional();
        $relays = $professional->getManageableRelays($beneficiary);

        if (1 === $relays->count()) {
            $relay = $relays->first();
            $manager->disaffiliateBeneficiary($beneficiary, $relay);
            $this->generateDisaffiliationFlashMessage($beneficiary, $relay, $translator);

            return $this->redirectToRoute('list_beneficiaries');
        }

        $formModel = (new DisaffiliateBeneficiaryFormModel())->setRelays($relays);
        $form = $this->createForm(DisaffiliateBeneficiaryType::class, $formModel)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($formModel->getRelays() as $relay) {
                $manager->disaffiliateBeneficiary($beneficiary, $relay);
                $this->generateDisaffiliationFlashMessage($beneficiary, $relay, $translator);
            }

            return $this->redirectToRoute('list_beneficiaries');
        }

        return $this->renderForm('v2/user_affiliation/beneficiary/disaffiliate_beneficiary.html.twig', [
            'form' => $form,
            'beneficiary' => $beneficiary,
        ]);
    }

    private function generateDisaffiliationFlashMessage(Beneficiaire $beneficiary, Centre $relay, TranslatorInterface $translator): void
    {
        $this->addFlash(
            'success',
            $translator->trans('disaffiliate_beneficiary', [
                '%beneficiary%' => $beneficiary->getUser()->getFullName(),
                '%relay%' => $relay->getNom(),
            ]),
        );
    }
}
