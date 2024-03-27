<?php

namespace App\Controller\Api;

use App\ControllerV2\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class SwitchUserLocaleController extends AbstractController
{
    #[Route('api/v3/users/switch-locale', methods: 'PATCH')]
    #[IsGranted('ROLE_USER')]
    public function switch(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $newLocale = $request->request->get('locale');
        $user = $this->getUser();
        if ($user && $newLocale) {
            $user->setLastLang($newLocale);
            $em->flush();
        }

        return $this->json($user);
    }
}
