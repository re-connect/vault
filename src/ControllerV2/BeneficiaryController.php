<?php

namespace App\ControllerV2;

use App\Entity\Beneficiaire;
use App\FormV2\FilterUser\FilterUserFormModel;
use App\FormV2\FilterUser\FilterUserType;
use App\FormV2\UserCreation\SecretQuestionType;
use App\Repository\BeneficiaireRepository;
use App\Repository\CentreRepository;
use App\Security\VoterV2\BeneficiaryVoter;
use App\ServiceV2\PaginatorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/beneficiaries')]
class BeneficiaryController extends AbstractController
{
    #[IsGranted(BeneficiaryVoter::MANAGE)]
    #[Route(path: '', name: 'list_beneficiaries', methods: ['GET'])]
    public function listBeneficiaries(
        Request $request,
        BeneficiaireRepository $repository,
        PaginatorService $paginator,
        CentreRepository $relayRepository,
    ): Response {
        $query = $request->query;
        $relay = $relayRepository->find($query->getInt('relay'));

        if ($relay && !$this->isGranted('MANAGE_BENEFICIARIES', $relay)) {
            throw $this->createAccessDeniedException();
        }

        $formModel = new FilterUserFormModel(
            $query->get('search'),
            $relay,
        );

        $form = $this->createForm(FilterUserType::class, $formModel, [
            'action' => $this->generateUrl('list_beneficiaries'),
            'attr' => ['data-controller' => 'ajax-list-filter'],
            'relays' => $this->getUser()->getAffiliatedRelaysWithBeneficiaryManagement(),
        ])->handleRequest($request);

        return $this->render($request->isXmlHttpRequest()
            ? 'v2/beneficiary/list/_beneficiaries_list.html.twig'
            : 'v2/beneficiary/list/beneficiaries.html.twig',
            [
                'beneficiaries' => $paginator->create(
                    $repository->findByAuthorizedProfessional(
                        $this->getUser()->getSubject(),
                        $formModel->search,
                        $formModel->relay,
                    ),
                    $request->query->getInt('page', 1),
                    PaginatorService::LIST_USER_LIMIT,
                ),
                'form' => $form,
            ],
        );
    }

    #[IsGranted('UPDATE', 'beneficiary')]
    #[Route(path: '/{id<\d+>}/set_secret_question', name: 'set_secret_question', methods: ['POST'])]
    public function setSecretQuestion(Request $request, Beneficiaire $beneficiary, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(SecretQuestionType::class, $beneficiary, [
            'action' => $this->generateUrl('set_secret_question', ['id' => $beneficiary->getId()]),
        ])->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
        }

        return $this->redirectToRoute('re_user_redirectUser');
    }
}
