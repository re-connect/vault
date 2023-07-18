<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class PrivateTransformer implements DataTransformerInterface
{
    /**
     * Transforms an object (Promotion) to a string (code).
     *
     * @return mixed|string
     */
    public function transform($value): mixed
    {
        if (null === $value) {
            return '';
        }
        if ($value->getBPrive()) {
            $value->setBPrive('prive');
        } else {
            $value->setBPrive('partage');
        }

        return $value;
    }

    /**
     * Transforms a string (code) to an object (Promotion).
     *
     * @return mixed|null
     */
    public function reverseTransform($value): mixed
    {
        if (!$value) {
            return null;
        }
        if ('partage' == $value->getBPrive()) {
            $value->setBPrive(false);
        } else {
            $value->setBPrive(true);
        }

        return $value;
    }
}
