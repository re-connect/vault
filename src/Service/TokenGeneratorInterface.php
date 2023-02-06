<?php

namespace App\Service;

interface TokenGeneratorInterface
{
    /**
     * @return string
     */
    public function generateToken();
}
