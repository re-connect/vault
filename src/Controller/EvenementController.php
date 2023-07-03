<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Entity\Rappel;
use App\Entity\User;
use App\Manager\ConsultationBeneficiaireManager;
use App\Provider\BeneficiaireProvider;
use App\Provider\EvenementProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\TransactionRequiredException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class EvenementController extends AbstractController
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function index(
        $id,
        ConsultationBeneficiaireManager $consultationBeneficiaireManager,
        BeneficiaireProvider $beneficiaireProvider
    ): Response {
        $beneficiaire = $beneficiaireProvider->getEntity($id);

        $consultationBeneficiaireManager->handleUserVisit($beneficiaire);

        return $this->render('app/evenement/list.html.twig', [
            'beneficiaire' => $beneficiaire,
        ]);
    }

    public function listByDistantId($distantId, $clientId, BeneficiaireProvider $beneficiaireProvider): RedirectResponse
    {
        $beneficiaire = $beneficiaireProvider->getEntityByDistantId($distantId);

        return $this->redirectToRoute('re_app_evenement_list', ['id' => $beneficiaire->getId()]);
    }

    public function add(
        $id,
        Request $request,
        EvenementProvider $provider,
        BeneficiaireProvider $beneficiaireProvider
    ): Response {
        $beneficiaire = $beneficiaireProvider->getEntity($id);

        $entity = new Evenement($beneficiaire);

        return $this->gestionForm($provider, $entity, $request);
    }

    /**
     * @param EvenementProvider $provider
     * @param Evenement         $entity
     * @param Request           $request
     * @param Rappel[]|null     $originalRappels
     */
    private function gestionForm($provider, $entity, $request, $originalRappels = null): Response
    {
        $form = $provider->getForm($entity);
        $form->handleRequest($request);

        $response = new Response();
        if ($request->isMethod(Request::METHOD_POST)) {
            if ($form->isSubmitted() && $form->isValid()) {
                if (null !== $originalRappels) {
                    foreach ($originalRappels as $rappel) {
                        if (false === $entity->getRappels()->contains($rappel)) {
                            if (null !== $rappel->getSms()) {
                                $rappel->setArchive(true);
                                $this->em->persist($rappel);
                            } else {
                                $entity->removeRappel($rappel);
                                $this->em->remove($rappel);
                            }
                        }
                    }
                }

                if ((null !== $user = $this->getUser()) && ($user instanceof User) && $user->isMembre()) {
                    $entity->setMembre($user->getSubjectMembre());
                }

                $provider->save($entity);

                return new Response('', Response::HTTP_ACCEPTED);
            }

            $response = new Response('', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->render('app/evenement/form.html.twig', [
            'form' => $form,
            'id' => $entity->getId(),
        ], $response);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     */
    public function edit(
        $id,
        Request $request,
        EvenementProvider $provider
    ): Response {
        $entity = $provider->getEntity($id);
        $entity->setRappels($entity->getRappels(false));

        $originalRappels = new ArrayCollection();

        foreach ($entity->getRappels() as $rappel) {
            if (!$rappel->getArchive()) {
                $originalRappels->add($rappel);
            }
        }

        return $this->gestionForm($provider, $entity, $request, $originalRappels);
    }
}
