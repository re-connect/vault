<?php

namespace App\Controller;

use App\Entity\BeneficiaireCentre;
use App\Entity\Centre;
use App\Entity\CreatorCentre;
use App\Entity\MembreCentre;
use App\Entity\Subject;
use App\Entity\User;
use App\Entity\UserCentre;
use App\Manager\CentreManager;
use App\Manager\SMSManager;
use App\Provider\CentreProvider;
use App\Security\Authorization\Voter\BeneficiaireVoter;
use App\Security\Authorization\Voter\MembreVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserCreationController extends AbstractController
{
    private SMSManager $SMSManager;
    private CentreManager $centreManager;
    private EntityManagerInterface $entityManager;
    private ?Request $request;
    private RequestStack $requestStack;
    private CentreProvider $centreProvider;
    private TranslatorInterface $translator;

    public function __construct(
        SMSManager $SMSManager,
        CentreManager $centreManager,
        EntityManagerInterface $entityManager,
        RequestStack $requestStack,
        CentreProvider $centreProvider,
        TranslatorInterface $translator
    ) {
        $this->SMSManager = $SMSManager;
        $this->centreManager = $centreManager;
        $this->entityManager = $entityManager;
        $this->request = $requestStack->getCurrentRequest();
        $this->requestStack = $requestStack;
        $this->centreProvider = $centreProvider;
        $this->translator = $translator;
    }

    public function doAjoutSubjectAction(Subject $subject, ?string $way = 'default'): Response
    {
        $centreSubject = $this->centreProvider->getCentresFromUserWithCentre($subject);
        $centresMembre = $this->getUser()->getSubject()->getHandledCentres();

        if (is_object($centresMembre)) {
            $centresMembre = $centresMembre->toArray();
        }

        /** variable permettant de stocker les centres déjà coché */
        $defaultValues = [];
        /** récupération de tout les centres */
        $arCentres = [];
        foreach ($centresMembre as $i => $iValue) {
            if (in_array($iValue, $centreSubject)) {
                $defaultValues[] = $iValue;
            }
            $arCentres[$i] = $iValue;
        }

        /* Par défaut on coche le premier centre */
        if (0 === count($defaultValues) && count($arCentres) > 0) {
            $defaultValues[] = $arCentres[0];
        }

        $form = $this->createFormBuilder()
            ->add('centres', EntityType::class, [
                'class' => Centre::class,
                'choices' => $centresMembre,
                'data' => $defaultValues,
                'label' => 'Centres',
                'required' => true,
                'expanded' => true,
                'multiple' => true,
            ])
            ->add('submit', SubmitType::class, ['label' => 'confirm'])
            ->getForm();

        $form->handleRequest($this->request);

        /* Validation du formulaire */
        if ($this->request->isMethod(Request::METHOD_POST)) {
            $countAssociate = 0;
            $countDesassociate = 0;

            /* On désassocie les centres qui étaient auparavant associés et qui ne sont plus cochés */
            /** @var Centre $value */
            foreach ($defaultValues as $key => $value) {
                /* @var Centre $associatingCentre */
                foreach ($centresMembre as $centre) {
                    if ($centre->getId() === $value->getId()) {
                        /** @var BeneficiaireCentre[] $beneficiairesCentres */
                        $beneficiairesCentres = $this->entityManager->getRepository(BeneficiaireCentre::class)->findByCentre($centre);
                    }
                }

                /**
                 * La valeur par défaut ne reflète pas forcément les affectations réelles.
                 * Dans le cas d'un bénéficiaire ajouté par exemple,
                 * la première case est cochée par défaut alors que le bénéficiaire n'y est pas forcément affecté.
                 */
                $isReallyInCentre = false;
                if (!empty($beneficiairesCentres)) {
                    foreach ($beneficiairesCentres as $beneficiairesCentre) {
                        if ($beneficiairesCentre->getBeneficiaire()->getUser()->getId() === $subject->getUser()->getId()) {
                            $isReallyInCentre = true;
                        }
                    }
                }

                if ($isReallyInCentre && !in_array($value, $form->get('centres')->getData())) {
                    $this->centreManager->deassociateUserWithCentres($subject, $value);
                    ++$countDesassociate;
                }
            }

            /* On associe aux nouveaux centres cochés */
            foreach ($form->get('centres')->getData() as $key => $value) {
                if (!in_array($value, $centreSubject)) {
                    $arDroits = null;
                    if ($subject->getUser()->isMembre()) {
                        $arDroits = [MembreCentre::TYPEDROIT_GESTION_BENEFICIAIRES => true];
                    }
                    $bForceAccept = 'remotely' === $way ? false : $subject->getIsCreating();
                    $this->centreManager
                        ->associateUserWithCentres(
                            $subject,
                            $value,
                            $this->getUser()->getSubjectMembre(),
                            $arDroits,
                            $bForceAccept
                        );
                }
            }

            /* Rechargement du sujet pour avoir les nouvelles données */
            $this->entityManager->refresh($subject);

            /** Gestion du créateur centre */
            $creatorCentre = $subject->getUser()->getCreatorCentre();
            $firstCentre = false === $subject->getCentres()->first() ? null : $subject->getCentres()->first();
            if ($firstCentre) {
                if ($creatorCentre && $firstCentre !== $creatorCentre->getEntity()) {
                    $subject->getUser()->removeCreator($creatorCentre);
                    $this->entityManager->remove($creatorCentre);
                } else {
                    $subject->addCreator((new CreatorCentre())->setEntity($firstCentre));
                }
            } elseif ($creatorCentre) {
                $subject->getUser()->removeCreator($creatorCentre);
                $this->entityManager->remove($creatorCentre);
            }

            /* Enregistrement du sujet */
            $this->entityManager->flush();

            /** @var UserCentre $userCentre */
            foreach ($subject->getUsersCentres() as $userCentre) {
                if (!$userCentre->getBValid()) {
                    ++$countAssociate;
                }
            }

            if ($subject->getIsCreating()) {
                return $this->redirect($this->generateUrl('creationBeneficiaireStep5', ['way' => $way]));
            }

            if ($countAssociate > 0) {
                if ($subject->getUser()->isBeneficiaire()) {
                    return $this->redirect($this->generateUrl('re_membre_doDoAjoutBeneficiaire', ['id' => $subject->getId()]));
                }

                return $this->redirect($this->generateUrl('re_membre_doDoAjoutMembre', ['id' => $subject->getId()]));
            }

            if ($countDesassociate > 0) {
                $this->addFlash('success', 'membre.arreterSuivi.success');
            }

            /* Si rien a été associé, on ne retourne pas vers la page de success d'association */
            if (0 === $countAssociate) {
                if ($this->getUser()->isGestionnaire()) {
                    return $this->redirect($this->generateUrl('re_gestionnaire_membres'));
                }
                if ($subject->getUser()->isBeneficiaire()) {
                    return $this->redirect($this->generateUrl('list_beneficiaries'));
                }

                return $this->redirect($this->generateUrl('re_membre_membres'));
            }
        }

        $template = 'user/subject/doAjoutSubject.html.twig';
        if ($subject->getIsCreating()) {
            $template = 'user/membre-beneficiaire/creationBeneficiaireStep4.html.twig';
        }

        return $this->render($template, [
            'subject' => $subject,
            'form' => $form,
            'way' => $way,
        ]);
    }

    public function doDoAjoutSubjectAction(Subject $subject): Response
    {
        return $this->render('user/subject/doDoAjoutSubject.html.twig', [
            'subject' => $subject,
        ]);
    }

    public function sendSmsCodeAction(Subject $subject): Response
    {
        $form = $this->createFormBuilder()
            ->add('code', TextType::class, ['label' => 'membre.sendSmsCode.codeLabel', 'attr' => ['style' => 'width: 100px;']])
            ->add('submit', SubmitType::class, ['label' => 'confirm', 'attr' => ['class' => 'btn-blue font-size-1']])
            ->getForm();

        $this->SMSManager->sendSmsActivation($subject);

        $form->handleRequest($this->request);

        if ($this->request->isMethod(Request::METHOD_POST)) {
            if ($form->get('code')->getData() === $subject->getRelayInvitationSmsCode()) {
                $this->centreManager->accepterTousCentreEnCommun($subject, $this->getUser()->getSubject());
                $session = $this->requestStack->getSession();
                if ($session instanceof Session) {
                    $session->getFlashBag()->set('success', 'membre.sendSmsCode.success');
                }
                if ($subject->getUser()->isMembre()) {
                    return $this->redirect($this->generateUrl('re_membre_membres'));
                }

                return $this->redirect($this->generateUrl('list_beneficiaries'));
            }

            $form->get('code')->addError(new FormError($this->translator->trans('membre.sendSmsCode.mauvaiseReponse')));
        }

        return $this->render('user/subject/sendSmsCode.html.twig', [
            'subject' => $subject,
            'form' => $form,
        ]);
    }

    public function arreterSuiviSubjectAction(Subject $subject)
    {
        /** @var User $user */
        $user = $this->getUser();
        $subjectUser = $subject->getUser();
        if ($user->isGestionnaire() && $subjectUser->isBeneficiaire()) {
            $redirectRoute = 're_gestionnaire_beneficiaires';
            if (false === $this->isGranted(BeneficiaireVoter::GESTION_BENEFICIAIRE, $subject)) {
                throw new AccessDeniedException("Vous n'avez pas le droit d'arrêter le suivi de ce bénéficiaire");
            }
        }
        if ($user->isGestionnaire() && $subjectUser->isMembre()) {
            $redirectRoute = 're_gestionnaire_membres';
            if (false === $this->isGranted(MembreVoter::GESTION_MEMBRE, $subject)) {
                throw new AccessDeniedException("Vous n'avez pas le droit d'arrêter le suivi de ce bénéficiaire");
            }
        }
        if ($user->isMembre() && $subjectUser->isBeneficiaire()) {
            $redirectRoute = 'list_beneficiaries';
            if (false === $this->isGranted(BeneficiaireVoter::GESTION_BENEFICIAIRE, $subject)) {
                throw new AccessDeniedException("Vous n'avez pas le droit d'arrêter le suivi de ce bénéficiaire");
            }
        }
        if ($user->isMembre() && $subjectUser->isMembre()) {
            $redirectRoute = 're_membre_membres';
            if (false === $this->isGranted(MembreVoter::GESTION_MEMBRE, $subject)) {
                throw new AccessDeniedException("Vous n'avez pas le droit d'arreter le suivi de ce bénéficiaire");
            }
        } else {
            $redirectRoute = 're_user_redirectUser';
        }

        $centresEnCommun = $this->centreManager->getCentreCommunUserWithCentres($subject, $this->getUser()->getSubject());
        $form = $this->createFormBuilder()
            ->add('centres', EntityType::class, [
                'class' => Centre::class,
                'choices' => $centresEnCommun,
                'label' => 'Centres',
                'required' => true,
                'expanded' => true,
                'multiple' => true,
            ])
            ->add('submit', SubmitType::class, ['label' => 'confirm'])
            ->getForm();

        $form->handleRequest($this->request);

        if ($this->request->isMethod(Request::METHOD_POST)) {
            if (array_key_exists('allCentres', $this->request->get('form'))) {
                foreach ($centresEnCommun as $centre) {
                    $this->centreManager->deassociateUserWithCentres($subject, $centre);
                    $session = $this->requestStack->getSession();
                    if ($session instanceof Session) {
                        $session->getFlashBag()->set('success', 'membre.arreterSuivi.success');
                    }

                    return $this->redirect($this->generateUrl($redirectRoute));
                }
            }

            if (0 === count($form->get('centres')->getData())) {
                return $this->redirect($this->generateUrl($redirectRoute));
            }
            foreach ($form->get('centres')->getData() as $value) {
                $this->centreManager->deassociateUserWithCentres($subject, $value);
            }

            return $this->redirect($this->generateUrl($redirectRoute));
        }

        if (1 === count($centresEnCommun)) {
            $this->centreManager->deassociateUserWithCentres($subject, $centresEnCommun[0]);
            $session = $this->requestStack->getSession();
            if ($session instanceof Session) {
                $session->getFlashBag()->set('success', 'membre.arreterSuivi.success');
            }

            return $this->redirect($this->generateUrl($redirectRoute));
        }

        return $this->render('user/subject/arreterSuiviSubject.html.twig', [
            'subject' => $subject,
            'centresEnCommun' => $centresEnCommun,
            'form' => $form,
        ]);

        //        return $this->redirect($this->generateUrl($redirectRoute, []));
    }
}
