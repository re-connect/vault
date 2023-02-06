<?php

namespace App\Admin;

use App\Entity\Adresse;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class AdresseAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('Informations')
            ->add('nom', null, [
                'label' => false,
            ])
            ->add('ville')
            ->add('codePostal')
            ->add('pays')
            ->add('lat')
            ->add('lng')
            ->end();

        $form->getFormBuilder()->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            if (!$data instanceof Adresse || $data->getPays()) {
                return;
            }

            $data->setPays('France');
            $event->setData($data);
        });
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('id')
            ->add('nom')
            ->add('ville')
            ->add('codePostal');
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id', null, ['route' => ['name' => 'edit']])
            ->addIdentifier('nom', null, ['route' => ['name' => 'edit']])
            ->addIdentifier('ville', null, ['route' => ['name' => 'edit']])
            ->addIdentifier('codePostal', null, ['route' => ['name' => 'edit']]);
    }
}
