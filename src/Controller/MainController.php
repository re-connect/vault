<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\Type\LoginTypeV2;
use App\Provider\CentreProvider;
use App\Provider\HomeProvider;
use App\Repository\FaqQuestionRepository;
use App\Repository\VerbatimRepository;
use App\Service\LanguageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class MainController extends AbstractController
{
    public function homeV2(
        Request $request,
        VerbatimRepository $verbatimRepository,
        AuthenticationUtils $authenticationUtils
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('re_user_redirectUser');
        }

        return $this->home($request, $verbatimRepository, $authenticationUtils);
    }

    #[Route('/home', name: 'home')]
    public function home(
        Request $request,
        VerbatimRepository $verbatimRepository,
        AuthenticationUtils $authenticationUtils
    ): Response {
        $form = $this->createForm(LoginTypeV2::class, null, [
            'action' => $this->generateUrl('re_main_login'),
        ]);
        // Get the last 4 Verbatims created in admin
        $verbatims = $verbatimRepository->findBy([], ['id' => 'desc'], 4);

        return $this->render('homeV2/pages/index.html.twig', [
            'host' => $request->headers->get('host'),
            'form' => $form->createView(),
            'verbatims' => $verbatims,
            'last_username' => $authenticationUtils->getLastUsername(),
            'auth_error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    public function pageVault(AuthenticationUtils $authenticationUtils): Response
    {
        $form = $this->createForm(LoginTypeV2::class, null, [
            'action' => $this->generateUrl('re_main_login'),
        ]);

        return $this->render('homeV2/pages/page-vault.html.twig', [
            'form' => $form->createView(),
            'features' => HomeProvider::VAULT_FEATURES_CONTENT,
            'product_is' => HomeProvider::VAULT_IS_CONTENT,
            'last_username' => $authenticationUtils->getLastUsername(),
            'auth_error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    public function pageRP(): Response
    {
        return $this->render('homeV2/pages/page-rp.html.twig', [
            'features' => HomeProvider::RP_FEATURES_CONTENT,
            'product_is' => HomeProvider::RP_IS_CONTENT,
        ]);
    }

    #[Route('/reconnect-accompagnement-numerique', name: 'digital_cares')]
    public function digitalCares(): Response
    {
        return $this->render('homeV2/pages/digital_cares.html.twig', [
            'features' => HomeProvider::DIGITAL_CARES_FEATURES_CONTENT,
            'product_is' => HomeProvider::DIGITAL_CARES_IS_CONTENT,
        ]);
    }

    #[Route('/public/newsletter-confirmation', name: 'newsletter_confirmation')]
    public function newsletterConfirmation(): Response
    {
        return $this->render('homeV2/pages/newsletter_confirmation.html.twig');
    }

    public function pageFAQ(FaqQuestionRepository $repository): Response
    {
        return $this->render('homeV2/pages/faq.html.twig', [
            'faqQuestions' => $repository->findBy([], ['position' => 'ASC']),
        ]);
    }

    public function contactV2(): Response
    {
        return $this->render('homeV2/pages/contact.html.twig');
    }

    public function getCenters(CentreProvider $provider): Response
    {
        return new JsonResponse([$provider->getAllCentresWithAddress()], 200);
    }

    public function changeLang($lang, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $request->setLocale(strtolower($lang));
        $request->getSession()->set('_locale', strtolower($lang));
        $referer = $request->headers->get('referer') ?: '/';

        if (str_contains($referer, 'lang=')) {
            $langQueryParam = substr($referer, strpos($referer, 'lang='), 8);
            $referer = str_replace($langQueryParam, '', $referer);
        }

        try {
            $user = $this->getUser();
            if ($user) {
                $user->setLastLang($lang);
                $em->flush();
            }
        } catch (\Exception) {
        }

        return $this->redirect($referer);
    }

    public function autoLogin(Request $request, EntityManagerInterface $em, TokenStorageInterface $tokenStorage, EventDispatcherInterface $eventDispatcher, TranslatorInterface $translator, RequestStack $requestStack): Response
    {
        $session = $requestStack->getSession();
        $token = $request->get('token');
        $userId = $request->get('userId');
        if (null === $token || null === $userId) {
            throw $this->createAccessDeniedException();
        }
        $user = $em->getRepository(User::class)->find($userId);
        if (null === $user) {
            throw $this->createAccessDeniedException();
        }
        $userToken = $user->getAutoLoginToken();
        $userTokenDate = $user->getAutoLoginTokenDeliveredAt();
        $tokenTimeElapsedInMinutes = ((new \DateTime())->getTimestamp() - $userTokenDate->getTimestamp()) / 60;
        if (null === $userToken || $token !== $userToken || $tokenTimeElapsedInMinutes > 30) {
            if ($session instanceof Session) {
                $session->getFlashBag()->set('error', $translator->trans('user.autoLogin.expired'));
            }
            $user->setAutoLoginToken();
            throw $this->createAccessDeniedException();
        }
        $em->flush();
        $token = new UsernamePasswordToken($user, 'public', $user->getRoles());
        $tokenStorage->setToken($token);
        $event = new InteractiveLoginEvent($request, $token);
        $eventDispatcher->dispatch($event, 'security.interactive_login');

        return $this->redirect($this->generateUrl('re_user_redirectUser'));
    }

    #[Route('/public/resetting-mail-translation', name: 'resetting_mail_translation', methods: ['GET'])]
    public function resettingMailTranslation(Request $request, TranslatorInterface $translator, LanguageService $languageService): Response
    {
        if ($lang = $request->query->get('lang')) {
            $languageService->setLocaleInSession($lang);
            if ($request->getLocale() !== $translator->getLocale()) {
                return $this->redirectToRoute('/resetting_mail_translation', ['lang' => $request->getLocale()]);
            }
        }

        return $this->render('homeV2/pages/mail-translation/resetting-password.html.twig');
    }

    #[Route('/public/shared-document-mail-translation', name: 'shared_document_mail_translation', methods: ['GET'])]
    public function sharedDocumentMailTranslation(Request $request, TranslatorInterface $translator, LanguageService $languageService): Response
    {
        if ($lang = $request->query->get('lang')) {
            $languageService->setLocaleInSession($lang);
            if ($request->getLocale() !== $translator->getLocale()) {
                return $this->redirectToRoute('/shared_document_mail_translation', ['lang' => $request->getLocale()]);
            }
        }

        return $this->render('homeV2/pages/mail-translation/shared-document.html.twig');
    }

    #[Route('/public/get-mailjet-form', name: 'get_mailjet_form', methods: ['GET'])]
    public function getMailjetForm(): Response
    {
        return new Response(file_get_contents('https://xzk0s.mjt.lu/wgt/xzk0s/54y/form?c=2e04c0ed'));
    }
}
