<?php

namespace App\Serializer\Normalizer;

use App\Entity\FolderIcon;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Folder icon names are translation keys: translate them in the user locale.
 */
class FolderIconNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const string ALREADY_CALLED = 'FOLDER_ICON_NORMALIZER_ALREADY_CALLED';

    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    #[\Override]
    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return !isset($context[self::ALREADY_CALLED]) && $data instanceof FolderIcon;
    }

    #[\Override]
    public function normalize($object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $context[self::ALREADY_CALLED] = true;
        $data = $this->normalizer->normalize($object, $format, $context);

        if (is_array($data) && isset($data['name'])) {
            $data['name'] = $this->translator->trans($data['name']);
        }

        return $data;
    }

    #[\Override]
    public function getSupportedTypes(?string $format): array
    {
        return [FolderIcon::class => false];
    }
}
