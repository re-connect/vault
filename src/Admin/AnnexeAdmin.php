<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

final class AnnexeAdmin extends AbstractAdmin
{
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('id')
            ->add('url')
            ->add('fichier')
            ->add('actif')
            ->add('dateAjout');
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('id')
            ->add('url')
            ->add('fichier')
            ->add('actif')
            ->add('dateAjout');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('url')
//            ->add('fichier', VichFileType::class, [
//                'required' => false,
//                'label' => 'Fichier',
//                'allow_delete' => true,
//                'download_link' => true,
//            ])
            ->add('actif');
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('url')
            ->add('fichier')
            ->add('actif')
            ->add('dateAjout');
    }
}
