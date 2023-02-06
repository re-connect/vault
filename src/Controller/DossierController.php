<?php

namespace App\Controller;

use App\Entity\Dossier;
use App\Entity\User;
use App\Provider\BeneficiaireProvider;
use App\Provider\DocumentProvider;
use App\Provider\DossierProvider;
use App\Security\Authorization\Voter\DonneePersonnelleVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class DossierController.
 */
class DossierController extends AbstractController
{
    public function telecharger(
        Request $request,
        Dossier $dossier,
        DocumentProvider $documentProvider,
        TranslatorInterface $translator
    ): Response {
        if (!$this->isGranted(DonneePersonnelleVoter::DONNEEPERSONNELLE_VIEW, $dossier)) {
            throw new AccessDeniedException("Vous n'avez pas le droit d'afficher les documents de ce beneficiaire");
        }
        $streamedResponse = $documentProvider->createZipFromFolder($dossier);
        if (!$streamedResponse) {
            $this->addFlash('error', $translator->trans('error_during_download'));
            $referer = $request->headers->get('referer');

            return $this->redirect($referer);
        }
        $streamedResponse->headers->set('Content-Type', 'application/zip');
        $streamedResponse->headers->set('Content-Disposition', 'attachment; filename="'.$dossier->getNom().'.zip"');

        return $streamedResponse;
    }

    public function add(
        $id,
        Request $request,
        DossierProvider $provider,
        BeneficiaireProvider $beneficiaireProvider,
        TranslatorInterface $translator
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $beneficiaire = $beneficiaireProvider->getEntity($id);
        $autocompleteFolderNames = [
            $translator->trans('health'),
            $translator->trans('housing'),
            $translator->trans('identity'),
            $translator->trans('tax'),
            $translator->trans('work'),
        ];

        $entity = (new Dossier())
            ->setBeneficiaire($beneficiaire)
            ->setBPrive($user->isBeneficiaire())
            ->setDeposePar($user);

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
            'autocompleteFolderNames' => $autocompleteFolderNames,
        ], $response);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function addSubfolder(int $id, Request $request, DossierProvider $provider): Response
    {
        $dossieParent = $provider->getEntity($id);

        $entity = (new Dossier())
            ->setBeneficiaire($dossieParent->getBeneficiaire())
            ->setDeposePar($this->getUser())
            ->setDossierParent($dossieParent);

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
}
