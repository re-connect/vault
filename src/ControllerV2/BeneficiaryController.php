<?php

namespace App\ControllerV2;

use App\Entity\Beneficiaire;
use App\FormV2\FilterUser\FilterUserFormModel;
use App\FormV2\FilterUser\FilterUserType;
use App\FormV2\UserCreation\SecretQuestionType;
use App\Repository\BeneficiaireRepository;
use App\ServiceV2\PaginatorService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/beneficiaries')]
class BeneficiaryController extends AbstractController
{
    #[Route(path: '', name: 'list_beneficiaries', methods: ['GET'])]
    #[IsGranted('ROLE_MEMBRE')]
    public function listBeneficiaries(Request $request, BeneficiaireRepository $repository, PaginatorService $paginator): Response
    {
        return $this->render('v2/beneficiary/list/beneficiaries.html.twig', [
            'beneficiaries' => $paginator->create(
                $repository->findByAuthorizedProfessional($this->getUser()->getSubject()),
                $request->query->getInt('page', 1),
                PaginatorService::LIST_USER_LIMIT,
            ),
            'form' => $this->createForm(FilterUserType::class, null, [
                'action' => $this->generateUrl('filter_beneficiaries'),
                'attr' => ['data-controller' => 'ajax-list-filter'],
                'relays' => $this->getUser()->getAffiliatedRelaysWithBeneficiaryManagement(),
            ]),
        ]);
    }

    #[Route(
        path: '/filter',
        name: 'filter_beneficiaries',
        methods: ['POST'],
        condition: 'request.isXmlHttpRequest()',
    )]
    #[IsGranted('ROLE_MEMBRE')]
    public function searchBeneficiaries(Request $request, BeneficiaireRepository $repository, PaginatorService $paginator): Response
    {
        $formModel = new FilterUserFormModel();
        $form = $this->createForm(FilterUserType::class, $formModel, [
            'action' => $this->generateUrl('filter_beneficiaries'),
            'attr' => ['data-controller' => 'ajax-list-filter'],
            'relays' => $this->getUser()->getAffiliatedRelaysWithBeneficiaryManagement(),
        ])->handleRequest($request);

        return new JsonResponse([
            'html' => $this->render('v2/beneficiary/list/_beneficiaries_list.html.twig', [
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
            ])->getContent(),
        ]);
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
