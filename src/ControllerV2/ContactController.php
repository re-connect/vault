<?php

namespace App\ControllerV2;

use App\Entity\Beneficiaire;
use App\Entity\Contact;
use App\FormV2\ContactType;
use App\FormV2\SearchType;
use App\ManagerV2\ContactManager;
use App\Repository\ContactRepository;
use App\ServiceV2\PaginatorService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ContactController extends AbstractController
{
    #[Route(path: '/beneficiary/{id}/contacts', name: 'contact_list', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('UPDATE', 'beneficiary')]
    public function list(
        Request $request,
        Beneficiaire $beneficiary,
        ContactRepository $repository,
        PaginatorService $paginator,
    ): Response {
        $searchForm = $this->createForm(SearchType::class, null, [
            'attr' => ['data-controller' => 'ajax-list-filter'],
            'action' => $this->generateUrl('contact_search', ['id' => $beneficiary->getId()]),
        ]);

        return $this->renderForm('v2/vault/contact/index.html.twig', [
            'beneficiary' => $beneficiary,
            'contacts' => $paginator->create(
                $this->isLoggedInUser($beneficiary->getUser())
                    ? $repository->findAllByBeneficiary($beneficiary)
                    : $repository->findSharedByBeneficiary($beneficiary),
                $request->query->getInt('page', 1),
            ),
            'form' => $searchForm,
        ]);
    }

    #[Route(
        path: '/beneficiary/{id}/contacts/search',
        name: 'contact_search',
        requirements: ['id' => '\d+'],
        methods: ['POST'],
        condition: 'request.isXmlHttpRequest()',
    )]
    #[IsGranted('UPDATE', 'beneficiary')]
    public function search(
        Beneficiaire $beneficiary,
        Request $request,
        ContactRepository $repository,
        PaginatorService $paginator
    ): Response {
        $searchForm = $this->createForm(SearchType::class, null, [
            'attr' => ['data-controller' => 'ajax-list-filter'],
            'action' => $this->generateUrl('contact_search', ['id' => $beneficiary->getId()]),
        ])->handleRequest($request);

        $search = $searchForm->get('search')->getData();

        return new JsonResponse([
            'html' => $this->renderForm('v2/vault/contact/_list.html.twig', [
                'contacts' => $paginator->create(
                    $this->isLoggedInUser($beneficiary->getUser())
                        ? $repository->searchByBeneficiary($beneficiary, $search)
                        : $repository->searchSharedByBeneficiary($beneficiary, $search),
                    $request->query->getInt('page', 1),
                ),
                'beneficiary' => $beneficiary,
                'form' => $searchForm,
            ])->getContent(),
        ]);
    }

    #[Route(
        path: '/beneficiary/{id}/contact/create',
        name: 'contact_create',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('UPDATE', 'beneficiary')]
    public function create(Beneficiaire $beneficiary, Request $request, EntityManagerInterface $em): Response
    {
        $contact = new Contact($beneficiary);
        $form = $this->createForm(ContactType::class, $contact, [
            'action' => $this->generateUrl('contact_create', ['id' => $beneficiary->getId()]),
            'private' => $this->getUser() === $beneficiary->getUser(),
        ])->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($contact);
            $em->flush();
            $this->addFlash('success', 'contact_created');

            return $this->redirectToRoute('contact_list', ['id' => $beneficiary->getId()]);
        }

        return $this->renderForm('v2/vault/contact/create.html.twig', [
            'form' => $form,
            'beneficiary' => $beneficiary,
        ]);
    }

    #[Route(path: '/contact/{id}/detail', name: 'contact_detail', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('UPDATE', 'contact')]
    public function detail(Contact $contact): Response
    {
        return $this->render('v2/vault/contact/detail.html.twig', [
            'contact' => $contact,
            'beneficiary' => $contact->getBeneficiaire(),
        ]);
    }

    #[Route(path: '/contact/{id}/edit', name: 'contact_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    #[IsGranted('UPDATE', 'contact')]
    public function edit(Contact $contact, Request $request, EntityManagerInterface $em): Response
    {
        $beneficiary = $contact->getBeneficiaire();
        $form = $this->createForm(ContactType::class, $contact, [
            'action' => $this->generateUrl('contact_edit', ['id' => $contact->getId()]),
            'private' => $contact->getBPrive(),
        ])->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'contact_updated');

            return $this->redirectToRoute('contact_list', ['id' => $beneficiary->getId()]);
        }

        return $this->renderForm('v2/vault/contact/edit.html.twig', [
            'form' => $form,
            'beneficiary' => $beneficiary,
        ]);
    }

    #[Route(path: '/contact/{id}/delete', name: 'contact_delete', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('UPDATE', 'contact')]
    public function delete(Contact $contact, EntityManagerInterface $em): Response
    {
        $em->remove($contact);
        $em->flush();
        $this->addFlash('success', 'contact.bienSupprime');

        return $this->redirectToRoute('contact_list', ['id' => $contact->getBeneficiaireId()]);
    }

    #[Route(
        path: 'contact/{id}/toggle-visibility',
        name: 'contact_toggle_visibility',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'PATCH'],
    )]
    #[IsGranted('TOGGLE_VISIBILITY', 'contact')]
    public function toggleVisibility(Request $request, Contact $contact, ContactManager $manager): Response
    {
        $manager->toggleVisibility($contact);

        return $request->isXmlHttpRequest()
            ? new JsonResponse($contact)
            : $this->redirectToRoute('contact_list', ['id' => $contact->getBeneficiaireId()]);
    }
}
