<?php

namespace App\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;

class DossierType extends DossierSimpleType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder
            ->remove('bPrive');
    }

    #[\Override]
    public function getName(): string
    {
        return 're_form_dossier';
    }
}
