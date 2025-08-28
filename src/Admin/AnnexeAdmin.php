<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Validator\Constraints\File;
use Vich\UploaderBundle\Form\Type\VichFileType;

final class AnnexeAdmin extends AbstractAdmin
{
    #[\Override]
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('id')
            ->add('url')
            ->add('fichier')
            ->add('actif')
            ->add('dateAjout');
    }

    #[\Override]
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('id')
            ->add('url')
            ->add('fichier')
            ->add('actif')
            ->add('dateAjout')
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
            ->add('url', null, $this->isCurrentRoute('edit') ? ['attr' => [
                'read_only' => true, ], 'disabled' => true] : [])
            ->add('fichierFile', VichFileType::class, [
                'required' => false,
                'label' => 'Fichier',
                'constraints' => [
                    new File(
                        extensions: ['pdf'],
                        extensionsMessage: 'Veuillez choisir un PDF valide',
                    ),
                ],
            ])
            ->add('actif');
    }

    #[\Override]
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
