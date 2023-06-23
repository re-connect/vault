<?php

namespace App\Controller;

use App\Api\Manager\ApiClientManager;
use App\Entity\Centre;
use App\Entity\Membre;
use App\Entity\User;
use App\Form\Factory\UserFormFactory;
use App\Form\Type\MembreSearchType;
use App\Manager\CentreManager;
use App\Provider\CentreProvider;
use App\Provider\MembreProvider;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_MEMBRE')]
final class MembreMembreController extends REController
{
    public function __construct(
        RequestStack $requestStack,
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager,
        private readonly CentreProvider $centreProvider,
        private readonly MembreProvider $membreProvider,
        private readonly CentreManager $centreManager,
        ApiClientManager $apiClientManager,
    ) {
        parent::__construct($requestStack, $translator, $entityManager, $apiClientManager);
    }

    /**
     * @throws \Exception
     */
    public function membres(): RedirectResponse
    {
        $centres = $this->centreProvider->getCentresFromUserWithCentre($this->getUser()->getSubjectMembre());

        $centreFirst = $centres[0];

        return $this->redirect($this->generateUrl('re_membre_membresCentre', ['id' => $centreFirst->getId()]));
    }

    public function membresCentre(Centre $centre): Response
    {
        return $this->render('user/membre-membre/membresCentre.html.twig', [
            'centre' => $centre,
        ]);
    }

    public function ajoutMembreSearch(Request $request, UserRepository $userRepository): Response
    {
        $form = $this->createForm(MembreSearchType::class);
        $form->handleRequest($request);
        if ($request->isMethod(Request::METHOD_POST)) {
            $nom = $form->get('nom')->getData();
            $prenom = $form->get('prenom')->getData();

            $foundUsers = [];
            if (empty($nom) && empty($prenom)) {
                $this->addFlash('error', 'Vous devez renseigner au moins un champ.');
            } else {
                $criterias = [
                    'u.nom' => $nom,
                    'u.prenom' => $prenom,
                ];

                $foundUsers = $userRepository->findMembresByCriterias($criterias);
            }

            return $this->render('user/membre-membre/ajoutMembreSearch.html.twig', [
                'form' => $form->createView(),
                'foundUsers' => $foundUsers,
            ]);
        }

        return $this->render('user/membre-membre/ajoutMembreSearch.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @throws \Exception
     */
    public function doAjoutMembre(Membre $otherMembre, UserCreationController $userCreationController): Response
    {
        return $userCreationController->doAjoutSubjectAction($otherMembre);
    }

    /**
     * @return mixed
     */
    public function doDoAjoutMembre(Membre $otherMembre, UserCreationController $userCreationController)
    {
        return $userCreationController->doDoAjoutSubjectAction($otherMembre);
    }

    /**
     * @throws \Exception
     */
    public function sendSmsCode(Membre $otherMembre, UserCreationController $userCreationController): Response
    {
        return $userCreationController->sendSmsCodeAction($otherMembre);
    }

    public function creationMembre(UserFormFactory $userFormFactory): Response
    {
        $form = $userFormFactory->getMembreForm($this->getUser()->getSubject()->getHandledCentres(), true);

        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid() && $this->request->isMethod(Request::METHOD_POST)) {
            /** @var User $user */
            $user = $form->get('user')->getData();
            $this->membreProvider->save($form->getData(), $this->getUser());

            $initiateur = $this->getUser()->getSubject();
            if ($this->getUser()->isGestionnaire()) {
                $initiateur = null;
            }

            $this->centreManager->associateUserWithCentres($form->getData(), $form->get('membresCentres')['centre']->getData(), $initiateur, $form->get('membresCentres')['droits']->getData(), true);

            $this->successFlashTranslate('member_successfully_created');

            return $this->redirect($this->generateUrl('re_membre_show_username', ['id' => $user->getId()]));
        }

        return $this->render('user/membre-membre/creationMembre.html.twig', [
            'form' => $form->createView(),
            'subject' => $form->getData(),
        ]);
    }

    /**
     * Après la création du membre on affiche une page avec le username du nouveau membre.
     */
    public function showUsername(User $user): Response
    {
        $centreId = $user->getSubjectMembre()->getMembresCentres()->first()->getCentre()->getId();

        if ($this->getUser()->isGestionnaire()) {
            $linkToList = $this->generateUrl('re_gestionnaire_membresCentre', ['id' => $centreId]);
        } else {
            $linkToList = $this->generateUrl('re_membre_membresCentre', ['id' => $centreId]);
        }

        return $this->render('user/membre-membre/membre_infos.html.twig', [
            'subject' => $user->getSubject(),
            'linkToList' => $linkToList,
        ]);
    }

    /**
     * @throws \Exception
     */
    public function arreterSuiviMembre(Membre $membre, UserCreationController $userCreationController): Response
    {
        return $userCreationController->arreterSuiviSubjectAction($membre);
    }
}
