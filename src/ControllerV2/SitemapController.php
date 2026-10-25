<?php

namespace App\ControllerV2;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Plan du site public (RGAA 4.1.2, critère 12.1 : au moins deux systèmes de navigation).
 */
class SitemapController extends AbstractController
{
    #[Route(path: '/plan-du-site', name: 'sitemap', methods: ['GET'])]
    public function show(): Response
    {
        return $this->render('home/pages/sitemap.html.twig');
    }
}
