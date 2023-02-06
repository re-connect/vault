<?php

namespace App\Admin;

use App\Entity\Client;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class ClientEntityAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $form): void
    {
        $client = $this->getSubject()->getClient();

        $form
            ->add('client', EntityType::class, [
                'class' => Client::class,
                'required' => true,
                'placeholder' => 'Choisir un client',
                'data' => $client,
            ])
            ->add('distantId', null, [
                'label' => 'Id correspondant',
                'required' => true,
            ]);
    }
}
