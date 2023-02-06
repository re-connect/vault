<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;

class PartenaireAdmin extends AbstractAdmin
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
            ->add('nom')
            ->add('link');
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('id')
            ->add('nom')
            ->add('createdAt')
            ->add('updatedAt')
            ->add('canada', null, ['label' => 'Canada']);
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id', null, ['route' => ['name' => 'edit']])
            ->addIdentifier('nom', null, ['route' => ['name' => 'edit']])
            ->addIdentifier('createdAt', null, ['route' => ['name' => 'edit']])
            ->addIdentifier('updatedAt', null, ['route' => ['name' => 'edit']])
            ->add('canada', null, ['label' => 'Canada']);
    }
}
