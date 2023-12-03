<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\Entity\PasswordResetSecretQuestion;
use App\Form\Entity\PasswordResetSMS;
use App\Form\Type\PasswordResetSecretQuestionType;
use App\Form\Type\PasswordResetSMSType;
use App\Manager\UserManager;
use App\ManagerV2\UserManager as UserManagerV2;
use App\RepositoryV2\ResetPasswordRequestRepository;
use App\ServiceV2\ResettingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @Route(options={"expose"=true})
 */
class PrivateResettingController extends AbstractController
{
    #[Route(path: '/user/{id}/unlock-password-reset', name: 'unlock_password_reset', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function unlock(
        Request $request,
        User $user,
        ResetPasswordRequestRepository $repository,
        EntityManagerInterface $em
    ): RedirectResponse {
        foreach ($repository->findBy(['user' => $user]) as $resetPasswordRequest) {
            $em->remove($resetPasswordRequest);
        }
        $em->flush();

        return $request->headers->get('referer')
            ? $this->redirect($request->headers->get('referer'))
            : $this->redirectToRoute('re_main_accueil');
    }
}
