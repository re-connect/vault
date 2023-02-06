<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AssociationController extends AbstractController
{
    public function accueil()
    {
        return $this->render('user/association/accueil.html.twig', []);
    }
}
