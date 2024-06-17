<?php

namespace App\Admin;

use App\Entity\User;
use App\Manager\UserManager;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;

class UserAdmin extends AbstractAdmin
{
    #[\Override]
    protected function configureDefaultSortValues(array &$sortValues): void
    {
        $sortValues[DatagridInterface::PAGE] = 1;
        $sortValues[DatagridInterface::SORT_ORDER] = 'DESC';
        $sortValues[DatagridInterface::SORT_BY] = 'id';
    }

    #[\Override]
    public function preUpdate($object = null): void
    {
        if ($object->getPlainPassword()) {
            $this->getConfigurationPool()->getContainer()->get(UserManager::class)->updatePassword($object);
        }
    }

    #[\Override]
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('Informations')
            ->add('username')
            ->add('nom')
            ->add('prenom')
            ->add('derniereConnexionAt', null, ['label' => 'Dernière connexion', 'required' => false, 'disabled' => true,
                'attr' => [
                    'read_only' => true,
                ], ])
            ->add('typeUser', 'choice', ['choices' => User::USER_TYPES])
            ->end();
    }

    #[\Override]
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('id')
            ->add('username', null, ['label' => "Nom d'utilisateur"])
            ->add('nom')
            ->add('prenom')
            ->add('typeUser', 'doctrine_orm_string', ['label' => 'Type d\'utilisateur'], 'choice')
            ->add('derniereConnexionAt', null, ['label' => 'Dernière connexion'])
            ->add('createdAt', null, ['label' => 'Date de création']);
    }

    #[\Override]
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id', null, ['route' => ['name' => 'edit']])
            ->add('username', null, ['label' => "Nom d'utilisateur"])
            ->add('nom')
            ->add('prenom')
            ->add('typeUser', 'choice', [
                'choices' => User::USER_TYPES,
                'label' => 'Type d\'utilisateur',
            ])
            ->add('derniereConnexionAt', null, ['label' => 'Dernière connexion'])
            ->add('createdAt', null, ['label' => 'Date de création']);
    }

    #[\Override]
    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->remove('create');
    }
}
