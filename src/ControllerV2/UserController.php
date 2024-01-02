<?php

namespace App\ControllerV2;

use App\Entity\Centre;
use App\Entity\User;
use App\FormV2\ChangePasswordFormType;
use App\FormV2\UserType;
use App\ManagerV2\RelayManager;
use App\ManagerV2\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
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
        $userForm = $this->createForm(UserType::class, $user)->handleRequest($request);

        $passwordForm = $this->createForm(ChangePasswordFormType::class, null, [
            'checkCurrentPassword' => true,
            'isBeneficiaire' => $user->isBeneficiaire(),
        ])->handleRequest($request);

        if ($userForm->isSubmitted() && $userForm->isValid()) {
            $usernameWillBeUpdated = $userManager->getUniqueUsername($user) !== $user->getUsername();
            $em->flush();
            $message = sprintf('settings_saved_successfully%s', $usernameWillBeUpdated ? '_username_updated' : '');
            $this->addFlash('success', $message);

            return $this->redirectToRoute('user_settings');
        }

        if ($passwordForm->isSubmitted()) {
            $currentPasswordInput = $passwordForm->get('currentPassword');
            if ($passwordForm->get('plainPassword')->get('first')->getData() !== $passwordForm->get('plainPassword')->get('second')->getData()) {
                $this->addFlash('error', $translator->trans('passwords_mismatch'));
            }
            if (!$userManager->isPasswordValid($user, $currentPasswordInput->getData())) {
                $currentPasswordInput->addError(new FormError($translator->trans('wrong_current_password')));
                $this->addFlash('error', $translator->trans('wrong_current_password'));
            }
            if ($passwordForm->isValid()) {
                $userManager->updatePassword($user, $passwordForm->get('plainPassword')->getData());
                $this->addFlash('success', 'password_updated_successfully');

                return $this->redirectToRoute('user_settings');
            }
        }

        return $this->render('v2/user/settings.html.twig', [
            'user' => $user,
            'userForm' => $userForm,
            'passwordForm' => $passwordForm,
        ]);
    }

    #[Route(path: '/delete', name: 'user_delete', methods: ['GET', 'POST'])]
    public function delete(Request $request, UserManager $userManager, TokenStorageInterface $tokenStorage): Response
    {
        $user = $this->getUser();

        if (!$this->isGranted('DELETE', $user)) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createFormBuilder()->getForm()->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userManager->remove($user);
            $request->getSession()->invalidate();
            $tokenStorage->setToken(null);

            return $this->redirectToRoute('re_main_login');
        }

        return $this->render('v2/user/delete.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    #[IsGranted('ROLE_MEMBRE')]
    #[Route(path: '/{id<\d+>}/toggle-invite/{relay<\d+>}', name: 'toggle_user_invitation', methods: ['GET'])]
    public function toggleUserInvitation(Request $request, User $user, #[MapEntity(id: 'relay')] Centre $relay, RelayManager $manager): Response
    {
        $manager->toggleUserInvitationToRelay($user, $relay);

        if ($request->isXmlHttpRequest()) {
            return $this->json([]);
        }

        return $user->isMembre()
            ? $this->redirectToRoute('invite_user', ['id' => $user->getId()])
            : $this->redirectToRoute('affiliate_beneficiary_relays', ['id' => $user->getSubjectBeneficiaire()?->getId()]);
    }

    #[IsGranted('ROLE_MEMBRE')]
    #[Route(path: '/{id<\d+>}/invite', name: 'invite_user', methods: ['GET'])]
    public function inviteUser(User $user): Response
    {
        return $this->render('v2/user/affiliation/invite.html.twig', ['user' => $user, 'relays' => $this->getUser()->getAffiliatedRelaysWithProfessionalManagement()]);
    }

    #[Route(
        path: '/{id}/disaffiliate/choose-relay',
        name: 'disaffiliate_relay_choice',
        requirements: ['id' => '\d+'],
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_MEMBRE')]
    #[IsGranted('UPDATE', 'user')]
    public function disaffiliateChooseRelay(User $user): Response
    {
        return $this->render('v2/user/disaffiliation/disaffiliate.html.twig', [
            'user' => $user,
            'relays' => $this->getProfessional()?->getManageableRelays($user),
        ]);
    }

    #[Route(
        path: '/{id}/relay/{relayId}/disaffiliate',
        name: 'disaffiliate_user',
        requirements: ['id' => '\d+'],
        methods: ['GET'],
        condition: 'request.isXmlHttpRequest()',
    )]
    #[IsGranted('ROLE_MEMBRE')]
    #[IsGranted('UPDATE', 'user')]
    public function disaffiliateFromRelay(
        User $user,
        #[MapEntity(id: 'relayId')] Centre $relay,
        RelayManager $manager,
    ): Response {
        $manager->removeUserFromRelay($user, $relay);

        return $this->json($user);
    }
}
