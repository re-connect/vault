<?php

namespace App\Admin;

use App\Entity\User;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class UserAssociationAdmin extends AbstractAdmin
{
    protected function configureDefaultSortValues(array &$sortValues): void
    {
        $sortValues[DatagridInterface::PAGE] = 1;
        $sortValues[DatagridInterface::SORT_ORDER] = 'DESC';
        $sortValues[DatagridInterface::SORT_BY] = 'id';
    }

    // Fields to be shown on create/edit form
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('Informations')
            ->add('username', null, ['label' => "Nom d'utilisateur"])
            ->end();

        $user = $this->getSubject();
        if (null === $user || !$user->getId()) {
            $form->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => ['label' => 'Mot de passe '],
                'second_options' => ['label' => 'Confirmer'],
                'invalid_message' => 'fos_user.password.mismatch',
            ])
                ->add('test', null, [
                    'label' => 'Compte test',
                ]);
        }

        // We want all new associations to be disabled
        $form->getFormBuilder()->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            if (!$data instanceof User || $data->getId()) {
                return;
            }

            $data->disable();
        });
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->remove('create');
    }
}
