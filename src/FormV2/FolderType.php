<?php

namespace App\FormV2;

use App\Entity\Attributes\FolderIcon;
use App\Entity\Dossier;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FolderType extends AbstractType
{
    public function __construct(private readonly Packages $packages)
    {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'name',
                'attr' => [
                    'data-action' => 'click->autocomplete#autocomplete',
                ],
            ])
            ->add('icon', EntityType::class, [
                'class' => FolderIcon::class,
                'required' => false,
                'label' => 'choose_picture',
                'choice_label' => 'name',
                'choice_translation_domain' => 'messages',
                'placeholder' => 'no_picture',
                'placeholder_attr' => [
                    'data-icon-file-path' => $this->packages->getUrl(Dossier::DEFAULT_ICON_FILE_PATH),
                ],
                'choice_attr' => fn ($choice) => [
                    'data-icon-file-path' => $this->packages->getUrl($choice->getPublicFilePath()),
                ],
                'attr' => [
                    'data-folder-icon-target' => 'input',
                    'data-action' => 'change->folder-icon#update',
                ],
            ]);

        if (!$options['rename_only']) {
            $builder
                ->add('bPrive', ChoiceType::class, [
                    'label' => 'access',
                    'multiple' => false,
                    'expanded' => true,
                    'choices' => [
                        'private' => true,
                        'shared' => false,
                    ],
                    'data' => $options['private'],
                ]);
        }
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Dossier::class,
            'rename_only' => false,
            'private' => true,
        ]);
    }
}
