<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class REImage2Type extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'width' => null,
            'height' => null,
            'empty_template' => null,
            'data_class' => null,
            'required' => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'width' => null,
            'height' => null,
            'empty_template' => null,
            'data_class' => null,
            'required' => false,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if (null === $options['width'] && null === $options['height']) {
            throw new \Exception('You have to define a width and a height for a reimage2 type');
        }
        $view->vars['width'] = $options['width'];
        $view->vars['height'] = $options['height'];
        $view->vars['empty_template'] = $options['empty_template'];
    }

    public function getParent(): ?string
    {
        return TextType::class;
    }

    public function getName(): string
    {
        return $this->getBlockPrefix();
    }

    public function getBlockPrefix(): string
    {
        return 're_image2';
    }
}
