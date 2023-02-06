<?php

namespace App\Controller;

use App\Entity\Beneficiaire;
use App\Entity\Contact;
use App\Manager\ConsultationBeneficiaireManager;
use App\Provider\BeneficiaireProvider;
use App\Provider\ContactProvider;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ContactController.
 */
class ContactController extends AbstractController
{
    public function index(
        Beneficiaire $beneficiaire,
        ConsultationBeneficiaireManager $consultationBeneficiaireManager,
        BeneficiaireProvider $beneficiaireProvider
    ): Response {
        $beneficiaire = $beneficiaireProvider->getEntity($beneficiaire->getId());
        $consultationBeneficiaireManager->handleUserVisit($beneficiaire);

        return $this->render('app/contact/list.html.twig', [
            'beneficiaire' => $beneficiaire,
        ]);
    }

    public function listByDistantId($distantId, $clientId, BeneficiaireProvider $beneficiaireProvider): RedirectResponse
    {
        $beneficiaire = $beneficiaireProvider->getEntityByDistantId($distantId);

        return $this->redirectToRoute('re_app_contact_list', ['id' => $beneficiaire->getId()]);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add($id, Request $request, ContactProvider $provider, BeneficiaireProvider $beneficiaireProvider): Response
    {
        $beneficiaire = $beneficiaireProvider->getEntity($id);

        $entity = new Contact($beneficiaire);

        return $this->gestionForm($provider, $entity, $request);
    }

    /**
     * @param ContactProvider $provider
     * @param Contact         $entity
     * @param Request         $request
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function gestionForm($provider, $entity, $request): Response
    {
        $form = $provider->getForm($entity);

        $form->handleRequest($request);

        if ($request->isMethod(Request::METHOD_POST)) {
            if ($form->isSubmitted() && $form->isValid()) {
                $provider->save($entity);

                return new Response('', Response::HTTP_ACCEPTED);
            }

            return new Response('', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->render('app/donnee-personnelle/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     */
    public function edit($id, Request $request, ContactProvider $provider): Response
    {
        $entity = $provider->getEntity($id);

        return $this->gestionForm($provider, $entity, $request);
    }
}
