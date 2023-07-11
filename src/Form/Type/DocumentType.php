<?php

namespace App\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;

class DocumentType extends DocumentSimpleType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder
            ->remove('bPrive');
    }

    public function getName(): string
    {
        return 're_form_document';
    }
}
