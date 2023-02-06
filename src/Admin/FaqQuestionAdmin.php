<?php

namespace App\Admin;

use Doctrine\DBAL\Types\DateType;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;

class FaqQuestionAdmin extends AbstractAdmin
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
            ->add('text', null, ['label' => 'question'])
            ->add('answer')
//            ->add('answer', CKEditorType::class, ['label' => 'answer'])
            ->add('position');
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('id')
            ->add('text', null, ['label' => 'question'])
            ->add('answer', null, ['label' => 'answer'])
            ->add('position')
            ->add('createdAt');
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id', null, ['route' => ['name' => 'edit']])
            ->addIdentifier('text', null, ['route' => ['name' => 'edit'], 'label' => 'question'])
            ->addIdentifier('position', null, ['route' => ['name' => 'edit']])
            ->addIdentifier('createdAt', DateType::class, ['route' => ['name' => 'edit'], 'label' => 'add_date']);
    }
}
