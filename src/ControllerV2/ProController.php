<?php

namespace App\ControllerV2;

use App\Entity\Attributes\Centre;
use App\Entity\Attributes\Membre;
use App\Entity\Attributes\User;
use App\FormV2\FilterUser\FilterUserFormModel;
use App\FormV2\FilterUser\FilterUserType;
use App\FormV2\UserAffiliation\Model\SearchProFormModel;
use App\FormV2\UserAffiliation\SearchProType;
use App\FormV2\UserCreation\CreateUserType;
use App\ManagerV2\UserManager;
use App\Repository\CentreRepository;
use App\Repository\MembreRepository;
use App\Security\VoterV2\ProVoter;
use App\ServiceV2\PaginatorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
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
        $relay = $relayRepository->find($query->getInt('relay'));
        $user = $this->getUser();

        if ($relay && !$this->isGranted('MANAGE_PRO', $relay)) {
            throw $this->createAccessDeniedException();
        }

        $formModel = new FilterUserFormModel(
            $query->get('search'),
            $relay,
        );

        $form = $this->createForm(FilterUserType::class, $formModel, [
            'action' => $this->generateUrl('list_pro'),
            'attr' => ['data-controller' => 'ajax-list-filter'],
            'relays' => $user?->getAffiliatedRelaysWithProfessionalManagement() ?? [],
        ])->handleRequest($request);

        $pro = $user?->getSubjectMembre();

        return $this->render($request->isXmlHttpRequest()
            ? 'v2/pro/list/_professionals_list.html.twig'
            : 'v2/pro/list/professionals.html.twig',
            [
                'professionals' => $paginator->create(
                    $pro
                        ? $repository->findByAuthorizedProfessional(
                            $pro,
                            $formModel->search,
                            $formModel->relay,
                        ) : [],
                    $request->query->getInt('page', 1),
                    PaginatorService::LIST_USER_LIMIT,
                ),
                'form' => $form,
                'relay' => $relay,
            ],
        );
    }

    #[Route(path: '/create/home', name: 'create_pro_home', methods: ['GET'])]
    public function createProHome(): Response
    {
        return $this->render('v2/pro/create/index.html.twig');
    }

    #[Route(path: '/create', name: 'create_pro', methods: ['GET', 'POST'])]
    public function createPro(Request $request, EntityManagerInterface $em, UserManager $manager): Response
    {
        $user = User::createPro();
        $form = $this->createForm(CreateUserType::class, $user, [
            'action' => $this->generateUrl('create_pro'),
        ])->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->addFlash('success', 'user_successfully_created');
            $manager->updatePasswordWithPlain($user);

            return $this->redirectToRoute('invite_user', ['id' => $user->getId()]);
        }

        return $this->render('v2/pro/create/create.html.twig', ['form' => $form]);
    }

    #[Route(path: '/search', name: 'search_pro', methods: ['GET', 'POST'])]
    public function searchPros(
        Request $request,
        MembreRepository $repository,
        PaginatorService $paginator,
        #[MapQueryParameter] ?string $firstname,
        #[MapQueryParameter] ?string $lastname,
    ): Response {
        $formModel = new SearchProFormModel(
            $firstname,
            $lastname,
        );
        $user = $this->getUser();
        $form = $this->createForm(SearchProType::class, $formModel, [
            'action' => $this->generateUrl('search_pro'),
        ])->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->redirectToRoute('search_pro', [
                'firstname' => $formModel->getFirstname(),
                'lastname' => $formModel->getLastname(),
            ]);
        }

        return $this->render('v2/pro/create/search.html.twig', [
            'form' => $form,
            'pros' => $user
                ? $paginator->create(
                    $repository->searchByUsernameInformation($user, $formModel->getFirstname(), $formModel->getLastname()),
                    $request->query->getInt('page', $request->query->getInt('page', 1)),
                )
                : [],
        ]);
    }

    #[Route(
        path: '/{id<\d+>}/relay/{relay<\d+>}/toggle-permission/{permission<[a-z]+>}',
        name: 'toggle_pro_permission',
        methods: ['GET', 'POST'],
        condition: "params['permission'] in [
        constant('App\\\Entity\\\MembreCentre::MANAGE_BENEFICIARIES_PERMISSION'),
        constant('App\\\Entity\\\MembreCentre::MANAGE_PROS_PERMISSION'),
        ]",
    )]
    #[IsGranted('UPDATE', 'pro')]
    #[IsGranted('MANAGE_PRO', 'relay')]
    public function togglePermission(
        Membre $pro,
        #[MapEntity(id: 'relay')] Centre $relay,
        string $permission,
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isGranted($permission, $pro->getUser()?->getUserRelay($relay))) {
            throw $this->createAccessDeniedException();
        }

        $pro->getUserCentre($relay)?->togglePermission($permission);
        $em->flush();

        return $this->render('v2/pro/list/_update_permission_button.html.twig', [
            'user' => $pro->getUser(),
            'relay' => $relay,
        ]);
    }
}
