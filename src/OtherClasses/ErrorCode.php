<?php

namespace App\OtherClasses;

class ErrorCode implements \Stringable
{
    public const VALIDATION_ERROR = 'validation_error';
    public const ACCESS_DENIED = 'access_denied';
    public const ENTITY_NOT_FOUND = 'entity_not_found';
    public const UNKNOW_ERROR = 'unknown_error';
    public const BAD_REQUEST = 'bad_request';

    private readonly string $label;

    public function __construct($exceptionClassName)
    {
        $this->label = match ($exceptionClassName) {
            'AccessDeniedException', 'AccessDeniedHttpException' => self::ACCESS_DENIED,
            'EntityNotFoundException', 'NotFoundHttpException' => self::ENTITY_NOT_FOUND,
            'BadRequestHttpException', 'UploadException' => self::BAD_REQUEST,
            default => self::UNKNOW_ERROR,
        };
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->label;
    }
}
