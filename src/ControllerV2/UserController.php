<?php

namespace App\ControllerV2;

use App\Entity\Centre;
use App\Entity\User;
use App\FormV2\ChangePasswordFormType;
use App\FormV2\UserAffiliation\AffiliateUserType;
use App\FormV2\UserAffiliation\Model\AffiliateUserModel;
use App\FormV2\UserSettingsType;
use App\ManagerV2\RelayManager;
use App\ManagerV2\UserManager;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/user')]
class UserController extends AbstractController
{
    #[Route(path: '/settings', name: 'user_settings', methods: ['GET', 'POST'])]
    public function settings(
        Request $request,
        EntityManagerInterface $em,
        UserManager $userManager,
        TranslatorInterface $translator,
    ): Response {
        $user = $this->getUser();

        $userForm = $this->createForm(UserSettingsType::class, $user)->handleRequest($request);

        $passwordForm = $this->createForm(ChangePasswordFormType::class, null, [
            'checkCurrentPassword' => true,
            'isBeneficiaire' => $user->isBeneficiaire(),
        ])->handleRequest($request);

        if ($userForm->isSubmitted()) {
            if ($userForm->isValid()) {
                $em->flush();
                $this->addFlash('success', 'settings_saved_successfully');

                return $this->redirectToRoute('user_settings');
            }
        }

        if ($passwordForm->isSubmitted()) {
            $currentPasswordInput = $passwordForm->get('currentPassword');
            if (!$userManager->isPasswordValid($user, $currentPasswordInput->getData())) {
                $currentPasswordInput->addError(new FormError($translator->trans('wrong_current_password')));
            }
            if ($passwordForm->isValid()) {
                $userManager->updatePassword($user, $passwordForm->get('plainPassword')->getData());
                $this->addFlash('success', 'password_updated_successfully');

                return $this->redirectToRoute('user_settings');
            }
        }

        return $this->render('v2/user/settings.html.twig', [
            'userForm' => $userForm,
            'passwordForm' => $passwordForm,
        ]);
    }

    #[Route(path: '/delete', name: 'user_delete', methods: ['GET', 'POST'])]
    public function delete(Request $request, UserManager $userManager, TokenStorageInterface $tokenStorage): Response
    {
        $user = $this->getUser();

        if (!$this->isGranted('DELETE', $user)) {
            throw new AccessDeniedException();
        }

        $form = $this->createFormBuilder()->getForm()->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userManager->remove($user);
            $request->getSession()->invalidate();
            $tokenStorage->setToken(null);

            return $this->redirectToRoute('re_main_login');
        }

        return $this->render('v2/user/delete.html.twig', [
            'submitForm' => $form,
        ]);
    }

    #[IsGranted('ROLE_MEMBRE')]
    #[Route(path: '/{id<\d+>}/invite', name: 'invite_user', methods: [Request::METHOD_GET, 'POST'])]
    public function inviteUser(Request $request, User $user, RelayManager $manager): Response
    {
        $relays = new AffiliateUserModel($user->getRelays());
        $loggedInUserRelays = $this->getUser()->getValidRelays();
        $form = $this->createForm(AffiliateUserType::class, $relays, [
            'action' => $this->generateUrl('invite_user', ['id' => $user->getId()]),
            'available_relays' => $loggedInUserRelays,
        ])->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $manager->updateUserRelays($user, $relays->relays, $loggedInUserRelays);

            return $this->redirectToRoute('list_professionals');
        }

        return $this->render('v2/user/invite.html.twig', ['form' => $form]);
    }

    #[Route(
        path: '/{id}/disaffiliate/choose-relay',
        name: 'disaffiliate_relay_choice',
        requirements: ['id' => '\d+'],
        methods: ['GET'],
    )]
    #[IsGranted('UPDATE', 'user')]
    public function disaffiliateChooseRelay(User $user): Response
    {
        return $this->render('v2/user_affiliation/beneficiary/disaffiliate_beneficiary.html.twig', [
            'user' => $user,
            'relays' => $this->getProfessional()?->getManageableRelays($user->getSubject()) ?: new ArrayCollection([]),
        ]);
    }

    #[Route(
        path: '/{id}/relay/{relayId}/disaffiliate',
        name: 'disaffiliate_user',
        requirements: ['id' => '\d+'],
        methods: ['GET'],
        condition: 'request.isXmlHttpRequest()',
    )]
    #[ParamConverter('relay', class: 'App\Entity\Centre', options: ['id' => 'relayId'])]
    #[IsGranted('UPDATE', 'user')]
    public function disaffiliateFromRelay(
        User $user,
        Centre $relay,
        RelayManager $manager,
    ): Response {
        $manager->removeUserFromRelay($user, $relay);

        return $this->json($user);
    }
}
