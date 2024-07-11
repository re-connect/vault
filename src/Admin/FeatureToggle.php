<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\Form\Type\DateTimePickerType;

class FeatureToggle extends AbstractAdmin
{
    #[\Override]
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id', null, ['route' => ['name' => 'edit']])
            ->add('name', null, ['label' => 'Nom'])
            ->add('description', null, ['label' => 'Description'])
            ->add('enabled', null, ['label' => 'Actif'])
            ->add('enableDate', null, ['label' => 'Actif à partir du']);
    }

    #[\Override]
    protected function configureFormFields(FormMapper $form): void
    {
        $form->add('name', null, ['label' => 'name'])
            ->add('description', null, ['label' => 'Description'])
            ->add('enabled', null, ['label' => 'enabled'])
            ->add('enableDate', DateTimePickerType::class, [
                'label' => 'Actif à partir du',
                'required' => false,
            ]);
    }
}
