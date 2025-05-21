<?php

namespace App\ControllerV2\Admin;

use App\Entity\Attributes\User;
use App\RepositoryV2\ResetPasswordRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(options: ['expose' => true])]
class ResetPasswordController extends AbstractController
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
