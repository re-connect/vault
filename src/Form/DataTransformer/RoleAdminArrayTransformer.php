<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class RoleAdminArrayTransformer implements DataTransformerInterface
{
    public function __construct()
    {
        // You'll need the $manager to lookup your objects later
    }

    public function reverseTransform($value): mixed
    {
        if (is_null($value)) {
            return $value;
        }

        $arRet = [];
        foreach ($value as $key => $val) {
            if ($val) {
                $arRet[] = $key;
            }
        }

        return $arRet;
    }

    public function transform($value): mixed
    {
        if (is_null($value)) {
            return $value;
        }

        // Here convert ids embedded in your array to objects,
        // or ArrayCollection containing objects

        $arRet = [];
        foreach ($value as $key => $val) {
            $arRet[$val] = true;
        }

        return $arRet;
    }
}
