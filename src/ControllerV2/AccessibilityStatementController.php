<?php

namespace App\ControllerV2;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Déclaration d'accessibilité RGAA (article 47 de la loi n° 2005-102 du 11 février 2005).
 */
class AccessibilityStatementController extends AbstractController
{
    #[Route(path: '/accessibilite', name: 'accessibility_statement', methods: ['GET'])]
    public function show(): Response
    {
        return $this->render('home/pages/accessibility_statement.html.twig');
    }
}
