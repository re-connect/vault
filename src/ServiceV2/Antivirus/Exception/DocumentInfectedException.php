<?php

declare(strict_types=1);

namespace App\ServiceV2\Antivirus\Exception;

/**
 * Thrown when the antivirus returned a definitive verdict: the file is infected.
 */
class DocumentInfectedException extends \RuntimeException
{
    public function __construct(string $message = 'document_upload_infected', ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
