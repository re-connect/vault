<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class RemoveSpacesTransformer implements DataTransformerInterface
{
    /**
     * Transforms an object (Promotion) to a string (code).
     *
     * @return string
     */
    public function transform($value): mixed
    {
        return $value;
    }

    /**
     * Transforms a string (code) to an object (Promotion).
     *
     * @param string $value
     *
     * @return string
     *
     * @throws TransformationFailedException if object (Promotion) is not found
     */
    public function reverseTransform($value): mixed
    {
        $value = preg_replace('#[ ]#', '', $value);

        return $value;
    }
}
