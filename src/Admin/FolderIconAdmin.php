<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Vich\UploaderBundle\Form\Type\VichFileType;

final class FolderIconAdmin extends AbstractAdmin
{
    #[\Override]
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('id')
            ->add('name', null, ['label' => 'Nom'])
            ->add('fileName', null, ['label' => 'Nom du fichier']);
    }

    #[\Override]
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('id')
            ->add('name', null, ['label' => 'Nom'])
            ->add('fileName', null, ['label' => 'Nom du fichier'])
            ->add('updatedAt', null, ['label' => 'Date de mise à jour'])
            ->add('_action', 'actions', [
                'actions' => [
                    'edit' => [],
                ],
            ]);
    }

    #[\Override]
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('name', null, $this->isCurrentRoute('edit') ? ['attr' => [
                'read_only' => true, ], 'disabled' => true] : [])
            ->add('imageFile', VichFileType::class, [
                'required' => false,
                'label' => 'Fichier',
            ]);
    }

    #[\Override]
    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('name')
            ->add('fileName');
    }
}
