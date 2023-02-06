<?php

namespace App\Exception;

use App\OtherClasses\ErrorCode;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class JsonResponseException
{
    private \Exception $exception;
    private int $codeError;

    public function __construct(\Exception $exception, int $codeError = Response::HTTP_INTERNAL_SERVER_ERROR)
    {
        $this->exception = $exception;
        $this->codeError = $codeError;

        $this->initialize();
    }

    public function initialize()
    {
        $code = $this->codeError;

        if (\method_exists($this->exception, 'getStatusCode') && $this->exception->getStatusCode() < 600) {
            $code = $this->exception->getStatusCode();
        } elseif (\method_exists($this->exception, 'getCode') && $this->exception->getCode() < 600) {
            $code = $this->exception->getCode();
        }

        $this->codeError = (null === $code || 0 === $code) ? Response::HTTP_INTERNAL_SERVER_ERROR : $code;
    }

    public function getResponse(): JsonResponse
    {
        try {
            $ref = new \ReflectionClass($this->exception);
            $shortName = $ref->getShortName();
        } catch (\ReflectionException $e) {
            $shortName = null;
        }

        $response = [
            'error' => [
                    'message' => $this->exception->getMessage(),
                    'status' => $this->codeError,
                    'code' => (string) (new ErrorCode($shortName)),
                ],
        ];

        return new JsonResponse($response, $this->codeError);
    }
}
