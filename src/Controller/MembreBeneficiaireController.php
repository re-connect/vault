<?php

namespace App\Controller;

use App\Api\Manager\ApiClientManager;
use App\Entity\Beneficiaire;
use App\Entity\Centre;
use App\Entity\Contact;
use App\Entity\CreatorUser;
use App\Event\BeneficiaireEvent;
use App\Event\REEvent;
use App\Form\Type\BeneficiaireSearchType;
use App\Form\Type\BeneficiaireTypeStep1;
use App\Form\Type\BeneficiaireTypeStep2;
use App\Form\Type\BeneficiaireTypeStep3;
use App\Manager\CentreManager;
use App\Manager\SMSManager;
use App\Manager\UserManager;
use App\Provider\BeneficiaireProvider;
use App\Provider\UserProvider;
use App\Repository\UserRepository;
use App\Security\Authorization\Voter\BeneficiaireVoter;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_MEMBRE')]
final class MembreBeneficiaireController extends REController
{
    private SessionInterface $session;
    private UserCreationController $userCreationController;
    private UserManager $userManager;
    private UserProvider $userProvider;
    private ValidatorInterface $validator;
    private BeneficiaireProvider $beneficiaireProvider;

    public function __construct(
        RequestStack $requestStack,
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager,
        UserCreationController $userCreationController,
        UserManager $userManager,
        UserProvider $userProvider,
        ValidatorInterface $validator,
        BeneficiaireProvider $beneficiaireProvider,
        ApiClientManager $apiClientManager
    ) {
        parent::__construct($requestStack, $translator, $entityManager, $apiClientManager);

        $this->session = $requestStack->getSession();
        $this->userCreationController = $userCreationController;
        $this->userManager = $userManager;
        $this->userProvider = $userProvider;
        $this->validator = $validator;
        $this->beneficiaireProvider = $beneficiaireProvider;
    }

    public function beneficiaires(Request $request, EntityManagerInterface $em): Response
    {
        $centre = null;
        if ($centreId = $request->get('id')) {
            $centre = $em->find(Centre::class, $centreId);
        }

        return $this->render('user/membre-beneficiaire/beneficiaires.html.twig', [
            'centre' => $centre,
        ]);
    }

    public function ajoutBeneficiaireSearch(Request $request, UserRepository $userRepository): Response
    {
        $foundUsers = null;
        $form = $this->createForm(BeneficiaireSearchType::class);
        $form->handleRequest($request);

        if ($this->request->isMethod(Request::METHOD_POST)) {
            $foundUsers = [];
            $nom = $form->get('nom')->getData();
            $prenom = $form->get('prenom')->getData();
            $dateNaissance = $form->get('dateNaissance')->getData();

            if (empty($nom) && empty($prenom) && empty($dateNaissance)) {
                $this->session->getFlashBag()->set('error', 'Vous devez renseigner au moins un champ.');
            } else {
                $criterias = [
                    'u.nom' => $nom,
                    'u.prenom' => $prenom,
                    'b.dateNaissance' => $dateNaissance,
                ];

                $foundUsers = $userRepository->findBeneficiairesByCriterias($criterias);
            }
        }

        return $this->render('user/membre-beneficiaire/ajoutBeneficiaireSearch.html.twig', [
            'form' => $form,
            'foundUsers' => $foundUsers,
        ]);
    }

    public function doDoAjoutBeneficiaire(Beneficiaire $beneficiaire): Response
    {
        return $this->userCreationController->doDoAjoutSubjectAction($beneficiaire);
    }

    public function questionSecrete(
        Beneficiaire $beneficiaire,
        UserManager $userManager,
        CentreManager $centreManager
    ): Response {
        $form = $this->createFormBuilder()
            ->add('reponse', TextType::class, ['label' => $beneficiaire->getQuestionSecrete()])
            ->add('submit', SubmitType::class, ['label' => 'confirm', 'attr' => ['class' => 'btn-blue font-size-1']])
            ->getForm();

        $form->handleRequest($this->request);

        $beneficiaire = $this->beneficiaireProvider->getBeneficiairesFromIdWithBeneficiairesWithCentres($beneficiaire->getId());

        if ($this->request->isMethod(Request::METHOD_POST)) {
            if ($userManager->compareSecretStrings($form->get('reponse')->getData(), $beneficiaire->getReponseSecrete())) {
                $centreManager->accepterTousCentreEnCommun($beneficiaire, $this->getUser()->getSubject());
                $this->session->getFlashBag()->set('success', 'membre.questionSecrete.success');

                return $this->redirect($this->generateUrl('list_beneficiaries'));
            }
            $form->get('reponse')->addError(new FormError($this->translator->trans('membre.questionSecrete.mauvaiseReponse')));
        }

        return $this->render('user/membre-beneficiaire/questionSecrete.html.twig', [
            'beneficiaire' => $beneficiaire,
            'form' => $form,
        ]);
    }

    public function sendSmsCode(Beneficiaire $beneficiaire): Response
    {
        return $this->userCreationController->sendSmsCodeAction($beneficiaire);
    }

    public function creationBeneficiaireStep1(string $way = 'default'): Response
    {
        $totalSteps = 'default' === $way ? 6 : 4;

        $beneficiaire = (new Beneficiaire())
            ->setCreePar($this->getUser())
            ->setDateNaissance(\DateTime::createFromFormat('d/m/Y', '01/01/1975'));

        $form = $this->createForm(BeneficiaireTypeStep1::class, $beneficiaire, ['way' => $way])
            ->handleRequest($this->request);

        if ($this->request->isMethod(Request::METHOD_POST)) {
            if (!$autoPassword = $this->session->get('autoPassword')) {
                $autoPassword = $this->userManager->randomPassword();
                $this->session->set('autoPassword', $autoPassword);
            }

            $user = $beneficiaire->getUser();
            $user->setPlainPassword($autoPassword);
            $this->userManager->updatePassword($user);

            if (null === $beneficiaire->getId() && !$beneficiaire->getCreatorUser()) {
                $user->addCreator((new CreatorUser())->setEntity($this->getUser()));
            }
        }

        $errors = [];
        if (($user = $beneficiaire->getUser()) !== null) {
            $groups = ['beneficiaire'];
            /* If account remotely, test the phone */
            if ('remotely' === $way) {
                $groups[] = 'beneficiaire-remotely';
            }
            $errors = $this->validator->validate($user, null, $groups);
        }

        if ($this->request->isMethod(Request::METHOD_POST) && 0 === count($errors)) {
            $beneficiaire->setCreePar($this->getUser());

            $this->entityManager->persist($beneficiaire);
            $this->entityManager->flush();

            $this->session->set('beneficiaireId', $beneficiaire->getId());

            $route = sprintf('creationBeneficiaireStep%d', 'default' === $way ? 2 : 4);

            return $this->redirect($this->generateUrl($route, ['way' => $way]));
        }

        foreach ($errors as $error) {
            $this->errorFlashTranslate($error->getPropertyPath().': '.$error->getMessage());
        }

        return $this->render(
            'user/membre-beneficiaire/creationBeneficiaireStep1.html.twig',
            [
                'form' => $form,
                'totalSteps' => $totalSteps,
            ]
        );
    }

    /**
     * @return RedirectResponse|Response
     */
    public function creationBeneficiaireStep2(UserManager $userManager)
    {
        /** @var Beneficiaire $beneficiaire */
        $beneficiaireId = $this->session->get('beneficiaireId');

        if (!empty($beneficiaireId)) {
            $beneficiaire = $this->beneficiaireProvider->getEntity($beneficiaireId, [], false);
        } else {
            return $this->redirect($this->generateUrl('re_membre_ajoutBeneficiaire'));
        }

        $form = $this->createForm(
            BeneficiaireTypeStep2::class,
            $beneficiaire,
            ['autoPassword' => $this->session->get('autoPassword')]
        );

        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->session->set('autoPassword', $form->get('user')->get('plainPassword')->getData());
            $userManager->updatePassword($beneficiaire->getUser());
            $this->entityManager->persist($beneficiaire);
            $this->entityManager->persist($beneficiaire->getUser());
            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('creationBeneficiaireStep3'));
        }

        return $this->render('user/membre-beneficiaire/creationBeneficiaireStep2.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * @return RedirectResponse|Response
     */
    public function creationBeneficiaireStep3(TranslatorInterface $translator)
    {
        if (!empty($beneficiaireId = $this->session->get('beneficiaireId'))) {
            $beneficiaire = $this->beneficiaireProvider->getEntity($beneficiaireId, [], false);
        } else {
            return $this->redirect($this->generateUrl('re_membre_ajoutBeneficiaire'));
        }

        $form = $this->createForm(BeneficiaireTypeStep3::class, $beneficiaire, [
            'validation_groups' => 'beneficiaire',
        ]);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($beneficiaire);
            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('creationBeneficiaireStep4'));
        }

        $secretQuestion = $beneficiaire->getQuestionSecrete();
        if (null !== $secretQuestion) {
            $arQuestions = [];
            foreach (Beneficiaire::getArQuestionsSecrete() as $key => $value) {
                $arQuestions[] = $translator->trans($value);
            }
            if (!in_array($secretQuestion, $arQuestions)) {
                $form->get('questionSecrete')->setData($translator->trans('membre.creationBeneficiaire.questionsSecretes.q9'));
                $form->get('autreQuestionSecrete')->setData($secretQuestion);
            }
        }

        return $this->render('user/membre-beneficiaire/creationBeneficiaireStep3.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * @return RedirectResponse|Response
     *
     * @throws \Exception
     */
    public function creationBeneficiaireStep4(string $way = 'default')
    {
        if (!empty($beneficiaireId = $this->session->get('beneficiaireId'))) {
            $beneficiaire = $this->beneficiaireProvider->getEntity($beneficiaireId, [], false);
        } else {
            return $this->redirect($this->generateUrl('re_membre_ajoutBeneficiaire'));
        }

        return $this->doAjoutBeneficiaire($beneficiaire, $way);
    }

    public function doAjoutBeneficiaire(Beneficiaire $beneficiaire, string $way = 'default'): Response
    {
        return $this->userCreationController->doAjoutSubjectAction($beneficiaire, $way);
    }

    public function creationBeneficiaireStep5(EventDispatcherInterface $eventDispatcher, SMSManager $SMSManager, string $way = 'default'): Response
    {
        if (!empty($beneficiaireId = $this->session->get('beneficiaireId'))) {
            $beneficiaire = $this->beneficiaireProvider->getEntity($beneficiaireId, [], false);
        } else {
            return $this->redirect($this->generateUrl('re_membre_ajoutBeneficiaire'));
        }
        $view = 'default' === $way ? 'user/membre-beneficiaire/creationBeneficiaireStep5.html.twig' : 'user/membre-beneficiaire/remotely/new_step_3.html.twig';

        $form = $this->createFormBuilder()
            ->add('submit', SubmitType::class, ['label' => 'confirm'])
            ->getForm();

        if ($this->request->isMethod(Request::METHOD_POST)) {
            if ('remotely' === $way) {
                try {
                    $password = $this->session->get('autoPassword');

                    $user = $beneficiaire->getUser();
                    $user->setPlainPassword($password);
                    $this->userManager->updatePassword($user);
                    $this->entityManager->flush();

                    $SMSManager->sendSMSBeneficiaryAddremotely($beneficiaire, $password);
                } catch (Exception) {
                    return $this->render($view, [
                        'beneficiaire' => $beneficiaire,
                        'form' => $form,
                        'way' => $way,
                        'totalSteps' => 4,
                    ]);
                }
            }

            $this->session->remove('autoPassword');
            $this->session->remove('beneficiaireId');
            $beneficiaire->setIsCreating(false);
            $this->entityManager->persist($beneficiaire);
            $this->entityManager->flush();

            $eventDispatcher->dispatch(new BeneficiaireEvent($beneficiaire, BeneficiaireEvent::BENEFICIAIRE_CREATED, $this->getUser()), REEvent::RE_EVENT_BENEFICIAIRE);

            $this->session->getFlashBag()->set('success', 'membre.creationBeneficiaire.success');

            return $this->redirect($this->generateUrl('creationBeneficiaireStep6', [
                'way' => $way,
                'id' => $beneficiaire->getId(),
            ]));
        }

        return $this->render($view, [
            'beneficiaire' => $beneficiaire,
            'form' => $form,
            'way' => $way,
            'totalSteps' => 'default' === $way ? 6 : 4,
        ]);
    }

    public function creationBeneficiaireStep6(Beneficiaire $beneficiary, string $way): Response
    {
        return $this->render('user/membre-beneficiaire/creationBeneficiaireStep6.html.twig', [
            'way' => $way,
            'beneficiary' => $beneficiary,
        ]);
    }

    /**
     * @throws \Exception
     */
    public function arreterSuiviBeneficiaire(
        Beneficiaire $beneficiaire,
        CentreManager $centreManager,
        UserCreationController $userCreationController
    ): Response {
        $user = $this->getUser();
        if ($user->isGestionnaire()) {
            count($user->getSubject()->getCentres());
        }

        $centresEnCommun = $centreManager->getCentreCommunUserWithCentres($beneficiaire, $user->getSubject());

        if (1 === count($centresEnCommun) && $this->request->isMethod(Request::METHOD_POST)) {
            return $this->render('user/membre-beneficiaire/arreterSuivi.html.twig', [
                'beneficiaire' => $beneficiaire,
                'form' => $this->createFormBuilder()
                    ->add('submit', SubmitType::class, ['label' => 'main.oui'])
                    ->add('allCentres', HiddenType::class, ['mapped' => false, 'data' => 1])
                    ->getForm(),
            ]);
        }

        if ($this->request->isMethod(Request::METHOD_POST)) {
            $this->session->getFlashBag()->set('success', 'membre.arreterSuivi.success');
            $userCreationController->arreterSuiviSubjectAction($beneficiaire);
        }

        return $userCreationController->arreterSuiviSubjectAction($beneficiaire);
    }

    public function ajoutContactBeneficiaire(Beneficiaire $beneficiaire, AuthorizationCheckerInterface $authorizationChecker): RedirectResponse
    {
        $user = $this->getUser();

        if (!$user->isMembre()) {
            throw new \RuntimeException("Vous ne pouvez pas ajouter votre contact à ce beneficiaire car vous n'êtes par membre");
        }
        if (false === $authorizationChecker->isGranted(BeneficiaireVoter::GESTION_BENEFICIAIRE, $beneficiaire)) {
            $this->createAccessDeniedException("Vous n'avez pas le droit d'ajouter votre contact à ce beneficiaire");
        }

        $contact = new Contact($beneficiaire);
        $contact
            ->setNom($user->getNom())
            ->setPrenom($user->getPrenom());

        if ($this->request->get('telephone') && $user->getTelephone()) {
            $contact->setTelephone($user->getTelephone());
        }
        if ($this->request->get('mail')) {
            $contact->setEmail($user->getEmail());
        }
        $this->entityManager->persist($contact);
        $this->entityManager->flush();
        $this->session->getFlashBag()->set('success', 'membre.partageContact.success');

        return $this->redirect($this->request->headers->get('referer'));
    }

    #[Route(
        path: '/membre/beneficiaires/creation-beneficiaire/reset',
        name: 'reset_beneficiary_creation',
        methods: ['GET']
    )]
    public function resetCreation(Request $request, EntityManagerInterface $em): Response
    {
        if (!empty($beneficiaryId = $this->session->get('beneficiaireId'))) {
            $beneficiary = $this->beneficiaireProvider->getEntity($beneficiaryId, [], false);
        } else {
            return $this->redirect($this->generateUrl('re_membre_ajoutBeneficiaire'));
        }

        $em->remove($beneficiary);
        $em->flush();
        $this->session->remove('beneficiaireId');
        $this->addFlash('success', 'beneficiary_creation_canceled');

        return $this->redirectToRoute('creationBeneficiaireStep1', ['way' => $request->query->get('way', 'default')]);
    }
}
