<?php

namespace App\Admin;

use App\Entity\MembreCentre;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelType;
use Sonata\Form\Type\ImmutableArrayType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class MembreCentreAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('Informations')
            ->add('centre', ModelType::class, [
                'label' => 'Centre',
                'btn_add' => false,
                'btn_delete' => false,
            ], [])
            ->add('bValid', null, ['label' => 'Accepté'])
            ->add('droits', ImmutableArrayType::class, [
                'keys' => [
                    [MembreCentre::DEFAULT_PERMISSION_CREATE_BENEFICIARIES, CheckboxType::class, ['disabled' => true, 'attr' => ['checked' => true]]],
                    [MembreCentre::MANAGE_BENEFICIARIES_PERMISSION, CheckboxType::class, ['attr' => ['checked' => true]]],
                    [MembreCentre::MANAGE_PROS_PERMISSION, CheckboxType::class, []],
                ],
            ])
            ->end();
    }
}
