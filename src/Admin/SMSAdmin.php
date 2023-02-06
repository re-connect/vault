<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;

class SMSAdmin extends AbstractAdmin
{
    protected function configureDefaultSortValues(array &$sortValues): void
    {
        $sortValues[DatagridInterface::PAGE] = 1;
        $sortValues[DatagridInterface::SORT_ORDER] = 'DESC';
        $sortValues[DatagridInterface::SORT_BY] = 'id';
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('Informations')
            ->add('id', null, ['attr' => ['read_only' => true], 'disabled' => true])
            ->add('dest', null, ['attr' => ['read_only' => true], 'disabled' => true])
            ->add('beneficiaire.user.username', null, ['attr' => ['read_only' => true], 'disabled' => true])
            ->add('beneficiaire.user.prenom', null, ['attr' => ['read_only' => true], 'disabled' => true])
            ->add('beneficiaire.user.nom', null, ['attr' => ['read_only' => true], 'disabled' => true]);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('id')
            ->add('dest')
            ->add('beneficiaire.user.username')
            ->add('beneficiaire.user.prenom')
            ->add('beneficiaire.user.nom')
            ->add('createdAt');
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id', null, ['route' => ['name' => 'edit']])
            ->addIdentifier('dest', null, ['route' => ['name' => 'edit']])
            ->addIdentifier('beneficiaire.user.prenom', null, ['route' => ['name' => 'edit']])
            ->addIdentifier('beneficiaire.user.nom', null, ['route' => ['name' => 'edit']])
            ->addIdentifier('createdAt', null, ['route' => ['name' => 'edit']]);
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->remove('create');
    }
}
