<?php

namespace App\Controller;

use App\Entity\Beneficiaire;
use App\Entity\Note;
use App\Manager\ConsultationBeneficiaireManager;
use App\Provider\BeneficiaireProvider;
use App\Provider\NoteProvider;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class NoteController.
 */
class NoteController extends AbstractController
{
    public function index(
        Beneficiaire $beneficiaire,
        ConsultationBeneficiaireManager $consultationBeneficiaireManager,
        BeneficiaireProvider $beneficiaireProvider
    ): Response {
        $beneficiaire = $beneficiaireProvider->getEntity($beneficiaire->getId());
        $consultationBeneficiaireManager->handleUserVisit($beneficiaire);

        return $this->render('app/note/list.html.twig', [
            'beneficiaire' => $beneficiaire,
        ]);
    }

    public function listByDistantId($distantId, $clientId, BeneficiaireProvider $beneficiaireProvider): RedirectResponse
    {
        $beneficiaire = $beneficiaireProvider->getEntityByDistantId($distantId);

        return $this->redirectToRoute('re_app_note_list', ['id' => $beneficiaire->getId()]);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add($id, Request $request, NoteProvider $provider, BeneficiaireProvider $beneficiaireProvider): Response
    {
        $beneficiaire = $beneficiaireProvider->getEntity($id);

        $entity = new Note($beneficiaire);

        $entity
            ->setBeneficiaire($beneficiaire)
            ->setDeposePar($this->getUser());

        return $this->gestionForm($provider, $entity, $request);
    }

    /**
     * @param NoteProvider $provider
     * @param Note         $entity
     * @param Request      $request
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function gestionForm($provider, $entity, $request): Response
    {
        $form = $provider->getForm($entity);

        $form->handleRequest($request);

        $response = new Response();
        if ($request->isMethod(Request::METHOD_POST)) {
            if ($form->isSubmitted() && $form->isValid()) {
                $provider->save($entity);

                return new Response('', Response::HTTP_ACCEPTED);
            }

            $response = new Response('', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->render('app/donnee-personnelle/form.html.twig', [
            'form' => $form->createView(),
        ], $response);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     */
    public function edit($id, Request $request, NoteProvider $provider): Response
    {
        $entity = $provider->getEntity($id);

        return $this->gestionForm($provider, $entity, $request);
    }
}
