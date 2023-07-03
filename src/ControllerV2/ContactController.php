<?php

namespace App\ControllerV2;

use App\Entity\Contact;
use App\FormV2\ContactType;
use App\ManagerV2\ContactManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ContactController extends AbstractController
{
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

            return $this->redirectToRoute('list_contacts', ['id' => $beneficiary->getId()]);
        }

        return $this->render('v2/vault/contact/edit.html.twig', [
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

        return $this->redirectToRoute('list_contacts', ['id' => $contact->getBeneficiaireId()]);
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
            : $this->redirectToRoute('list_contacts', ['id' => $contact->getBeneficiaireId()]);
    }
}
