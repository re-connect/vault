<?php

namespace App\ControllerV2;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OldResetPasswordController extends AbstractController
{
    #[Route(path: '/public/reinitialiser-mot-de-passe', name: 'app_reset_password_old', methods: ['GET'])]
    public function choice(): Response
    {
        return $this->redirectToRoute('app_forgot_password_request_choose');
    }
}
