<?php

declare(strict_types=1);

namespace App\ServiceV2\Antivirus;

use App\ServiceV2\Antivirus\Exception\AntivirusUnavailableException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Streams a file to the clamd daemon using the INSTREAM protocol.
 *
 * Talks to clamd directly over a socket (TCP or Unix) instead of shelling out to
 * `clamdscan --fdpass`, which avoids the file-descriptor passing blocked by the
 * php-fpm seccomp filter.
 */
class ClamavScanner
{
    /** Size of each INSTREAM chunk sent to clamd (bytes). */
    private const int CHUNK_SIZE = 8192;

    private const int TIMEOUT = 30;

    public function __construct(
        private readonly string $clamavDsn,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function isClean(File $file): bool
    {
        try {
            $response = $this->scan($file);
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('Document upload AV check failed, cause : %s', $e->getMessage()));

            throw new AntivirusUnavailableException(previous: $e);
        }

        // clamd replies "stream: OK" when clean, "stream: <name> FOUND" when infected.
        if (str_ends_with($response, 'OK')) {
            return true;
        }

        if (str_contains($response, 'FOUND')) {
            $this->logger->warning(sprintf('Document upload AV check found a threat: %s', $response));

            return false;
        }

        $this->logger->error(sprintf('Document upload AV check got an unexpected clamd response: %s', $response));

        throw new AntivirusUnavailableException();
    }

    /**
     * Streams the file to clamd via INSTREAM and returns the raw daemon response.
     *
     * Isolated in its own method so tests can override the socket I/O.
     */
    protected function scan(File $file): string
    {
        $handle = null;
        $socket = null;

        try {
            $socket = @stream_socket_client($this->clamavDsn, $errno, $errstr, self::TIMEOUT);
            if (false === $socket) {
                throw new \RuntimeException(sprintf('Unable to connect to clamd (%d): %s', $errno, $errstr));
            }
            stream_set_timeout($socket, self::TIMEOUT);

            // Start an in-band stream scan.
            fwrite($socket, "zINSTREAM\0");

            $handle = fopen($file->getPathname(), 'rb');
            if (false === $handle) {
                throw new \RuntimeException(sprintf('Unable to open file for scanning: %s', $file->getPathname()));
            }

            // Each chunk is prefixed with its length as a 4-byte big-endian integer.
            while (!feof($handle)) {
                $chunk = fread($handle, self::CHUNK_SIZE);
                if (false === $chunk || '' === $chunk) {
                    continue;
                }
                fwrite($socket, pack('N', strlen($chunk)).$chunk);
            }

            // A zero-length chunk terminates the stream.
            fwrite($socket, pack('N', 0));

            return trim((string) stream_get_contents($socket), "\0\n ");
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
            if (is_resource($socket)) {
                fclose($socket);
            }
        }
    }
}
