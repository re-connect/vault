<?php

namespace App\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;

class NoteType extends NoteSimpleType
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

    public function getName(): string
    {
        return 're_form_note';
    }
}
