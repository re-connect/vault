<?php

namespace App\Serializer\Normalizer;

use App\Entity\Document;
use App\Provider\DocumentProvider;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class DocumentNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'DOCUMENT_NORMALIZER_ALREADY_CALLED';
    private DocumentProvider $provider;

    public function __construct(DocumentProvider $provider)
    {
        $this->provider = $provider;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return false;
    }

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return $data instanceof Document;
    }

    public function normalize($object, string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        /* @var Document $object */
        $this->provider->hydrateDocumentWithUris($object);
        $context[self::ALREADY_CALLED] = true;

        return $this->normalizer->normalize($object, $format, $context);
    }
}
