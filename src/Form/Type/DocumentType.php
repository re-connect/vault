<?php

namespace App\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;

class DocumentType extends DocumentSimpleType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder
            ->remove('bPrive');
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 're_form_document';
    }
}
