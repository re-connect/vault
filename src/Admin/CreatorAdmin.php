<?php

namespace App\Admin;

use App\Entity\Centre;
use App\Entity\Client;
use App\Entity\CreatorCentre;
use App\Entity\CreatorClient;
use App\Entity\CreatorUser;
use App\Entity\User;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class CreatorAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $form): void
    {
        $subject = $this->getSubject();

        if ($subject instanceof CreatorCentre) {
            $form->add('entity', EntityType::class, [
                'label' => 'Centre',
                'class' => Centre::class,
                'disabled' => true,
            ]);
        } elseif ($subject instanceof CreatorUser) {
            $form->add(
                'entity',
                EntityType::class,
                [
                    'label' => 'Utilisateur',
                    'class' => User::class,
                    'disabled' => true,
                ],
                ['admin_code' => 'sonata.admin.user_simple']
            );
        } elseif ($subject instanceof CreatorClient) {
            $form->add('entity', EntityType::class, [
                'label' => 'Client',
                'class' => Client::class,
                'disabled' => true,
            ]);
        }
    }
}
