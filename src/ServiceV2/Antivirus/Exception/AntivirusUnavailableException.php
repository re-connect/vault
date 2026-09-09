<?php

declare(strict_types=1);

namespace App\ServiceV2\Antivirus\Exception;

/**
 * Thrown when the antivirus could not reach a verdict (daemon unreachable,
 * timeout, unexpected/malformed response).
 */
class AntivirusUnavailableException extends \RuntimeException
{
    public function __construct(string $message = 'document_scan_unavailable', ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
