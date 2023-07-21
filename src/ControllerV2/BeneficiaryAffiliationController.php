<?php

namespace App\ControllerV2;

use App\Entity\Beneficiaire;
use App\FormV2\AnswerSecretQuestionType;
use App\FormV2\UserAffiliation\Model\SearchBeneficiaryFormModel;
use App\FormV2\UserAffiliation\RelayAffiliationSmsCodeType;
use App\FormV2\UserAffiliation\SearchBeneficiaryType;
use App\Manager\SMSManager;
use App\ManagerV2\BeneficiaryAffiliationManager;
use App\ServiceV2\PaginatorService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
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
        $birthDate = $request->query->getAlnum('birthdate');
        $searchBeneficiaryModel = (new SearchBeneficiaryFormModel(
            $request->query->getAlnum('firstname'),
            $request->query->getAlnum('lastname'),
            $birthDate ? new \DateTime($request->query->getAlnum('birthdate')) : null,
        ));

        $searchForm = $this->createForm(SearchBeneficiaryType::class, $searchBeneficiaryModel, [
            'action' => $this->generateUrl('affiliate_beneficiary_search'),
        ])->handleRequest($request);

        $beneficiaries = $manager->getBeneficiariesFromSearch($searchBeneficiaryModel);

        return $this->render('v2/user_affiliation/beneficiary/affiliate_beneficiary_search.html.twig', [
            'form' => $searchForm,
            'beneficiaries' => $paginator->create($beneficiaries, $request->query->getInt('page', 1)),
            'search' => $searchBeneficiaryModel,
        ]);
    }

    #[Route(
        path: '/beneficiary/{id}/affiliate/relays',
        name: 'affiliate_beneficiary_relays',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST'],
    )]
    public function relays(Beneficiaire $beneficiary): Response
    {
        return $this->render('v2/user_affiliation/beneficiary/relays_form.html.twig', [
            'beneficiary' => $beneficiary,
        ]);
    }

    #[Route(
        path: '/beneficiary/{id}/affiliate/relays/secret_question',
        name: 'affiliate_beneficiary_relays_secret_question',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST'],
    )]
    public function relaysSecretQuestion(
        Request $request,
        Beneficiaire $beneficiary,
        BeneficiaryAffiliationManager $manager,
    ): Response {
        $secretQuestionForm = $this->createForm(AnswerSecretQuestionType::class, $beneficiary, [
            'action' => $this->generateUrl('affiliate_beneficiary_relays_secret_question', ['id' => $beneficiary->getId()]),
        ])->handleRequest($request);

        if ($secretQuestionForm->isSubmitted() && $secretQuestionForm->isValid()) {
            $manager->forceAcceptInvitations($beneficiary);
            $this->addFlash('success', 'beneficiary_added_to_relays');

            return $this->redirectToRoute('affiliate_beneficiary_relays', ['id' => $beneficiary->getId()]);
        }

        return $this->render('v2/user_affiliation/beneficiary/_relays_form_secret_question.html.twig', [
            'beneficiary' => $beneficiary,
            'secretQuestionForm' => $secretQuestionForm,
        ]);
    }

    /** @throws \Exception */
    #[Route(
        path: '/beneficiary/{id}/affiliate/relays/sms_code',
        name: 'affiliate_beneficiary_relays_sms_code',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST'],
    )]
    public function relaysSmsCode(
        Request $request,
        Beneficiaire $beneficiary,
        BeneficiaryAffiliationManager $manager,
        TranslatorInterface $translator,
        SMSManager $SMSmanager,
    ): Response {
        if (!$beneficiary->getUser()?->getTelephone()) {
            $this->addFlash('error', $translator->trans('beneficiary_has_no_phone_number'));

            return $this->redirectToRoute('affiliate_beneficiary_relays', ['id' => $beneficiary->getId()]);
        }

        if (!$beneficiary->hasValidSmsRelayInvitationCode() && $request::METHOD_GET === $request->getMethod()) {
            $SMSmanager->sendAffiliationCodeSms($beneficiary);
        }

        $smsCodeForm = $this->createForm(RelayAffiliationSmsCodeType::class, $beneficiary, [
            'action' => $this->generateUrl('affiliate_beneficiary_relays_sms_code', ['id' => $beneficiary->getId()]),
        ])
            ->handleRequest($request);

        if ($smsCodeForm->isSubmitted() && $smsCodeForm->isValid()) {
            $manager->forceAcceptInvitations($beneficiary);
            $manager->resetAffiliationSmsCode($beneficiary);
            $this->addFlash('success', 'beneficiary_added_to_relays');

            return $this->redirectToRoute('affiliate_beneficiary_relays', ['id' => $beneficiary->getId()]);
        }

        return $this->render('v2/user_affiliation/beneficiary/_relays_form_sms_code.html.twig', [
            'beneficiary' => $beneficiary,
            'smsCodeForm' => $smsCodeForm,
        ]);
    }
}
