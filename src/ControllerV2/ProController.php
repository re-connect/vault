<?php

namespace App\ControllerV2;

use App\Entity\Centre;
use App\Entity\Membre;
use App\Entity\User;
use App\FormV2\FilterUser\FilterUserFormModel;
use App\FormV2\FilterUser\FilterUserType;
use App\FormV2\Search\SearchFormModel;
use App\FormV2\Search\SearchType;
use App\FormV2\UserCreation\CreateUserType;
use App\ManagerV2\UserManager;
use App\Repository\CentreRepository;
use App\Repository\MembreRepository;
use App\Security\VoterV2\ProVoter;
use App\ServiceV2\PaginatorService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/pro')]
#[IsGranted(ProVoter::MANAGE)]
class ProController extends AbstractController
{
    #[Route(path: '', name: 'list_pro', methods: ['GET'])]
    public function listPros(
        Request $request,
        MembreRepository $repository,
        PaginatorService $paginator,
        CentreRepository $relayRepository,
    ): Response {
        $query = $request->query;
        $formModel = new FilterUserFormModel(
            $query->get('search'),
            $relayRepository->find($query->getInt('relay')),
        );

        $form = $this->createForm(FilterUserType::class, $formModel, [
            'action' => $this->generateUrl('list_pro'),
            'attr' => ['data-controller' => 'ajax-list-filter'],
            'relays' => $this->getUser()->getAffiliatedRelaysWithProfessionalManagement(),
        ])->handleRequest($request);

        return $this->render($request->isXmlHttpRequest()
            ? 'v2/pro/list/_professionals_list.html.twig'
            : 'v2/pro/list/professionals.html.twig',
            [
                'professionals' => $paginator->create(
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

    #[Route(path: '/create/home', name: 'create_pro_home', methods: ['GET'])]
    public function createProHome(): Response
    {
        return $this->render('v2/pro/create/index.html.twig', [
            'form' => $this->createForm(SearchType::class, new SearchFormModel(), [
                'action' => $this->generateUrl('search_pro'),
            ]),
        ]);
    }

    #[Route(path: '/create', name: 'create_pro', methods: ['GET', 'POST'])]
    public function createPro(Request $request, EntityManagerInterface $em, UserManager $manager): Response
    {
        $user = (new User())->setSubjectMembre(new Membre());
        $form = $this->createForm(CreateUserType::class, $user, [
            'action' => $this->generateUrl('create_pro'),
        ])->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $manager->updatePasswordWithPlain($user);

            return $this->redirectToRoute('invite_user', ['id' => $user->getId()]);
        }

        return $this->render('v2/pro/create/create.html.twig', ['form' => $form]);
    }

    #[Route(path: '/search', name: 'search_pro', methods: ['GET', 'POST'])]
    public function searchPros(Request $request, MembreRepository $repository, PaginatorService $paginator): Response
    {
        $search = new SearchFormModel($request->query->get('q'));
        $form = $this->createForm(SearchType::class, $search, [
            'action' => $this->generateUrl('search_pro'),
        ])->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->redirectToRoute('search_pro', ['q' => $search->getSearch()]);
        }

        $pros = $paginator->create(
            $repository->search($search->getSearch()),
            $request->query->getInt('page', $request->query->getInt('page', 1)),
        );

        return $request->isXmlHttpRequest()
            ? $this->render('v2/pro/create/_search_results_card.html.twig', ['pros' => $pros])
            : $this->render('v2/pro/create/search.html.twig', ['form' => $form, 'pros' => $pros]);
    }

    #[Route(
        path: '/{id}/relay/{relayId}/toggle-right/{right}',
        name: 'pro_toggle_right',
        requirements: ['id' => '\d+', 'relayId' => '\d+', 'right' => '[a-z]+'],
        methods: ['PATCH'],
        condition: "request.isXmlHttpRequest() and
        params['right'] in [
        constant('App\\\Entity\\\MembreCentre::TYPEDROIT_GESTION_BENEFICIAIRES'),
        constant('App\\\Entity\\\MembreCentre::TYPEDROIT_GESTION_MEMBRES'),
        ]",
    )]
    #[ParamConverter('relay', class: 'App\Entity\Centre', options: ['id' => 'relayId'])]
    #[IsGranted('UPDATE', 'pro')]
    public function toggleRight(Membre $pro, Centre $relay, string $right, EntityManagerInterface $em): JsonResponse
    {
        $pro->getUserCentre($relay)?->toggleRight($right);
        $em->flush();

        return new JsonResponse($pro);
    }
}
