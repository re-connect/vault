<?php

namespace App\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;

class ContactType extends ContactSimpleType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        $builder
            ->remove('bPrive');
    }

    #[\Override]
    public function getName(): string
    {
        return 're_form_contact';
    }
}
