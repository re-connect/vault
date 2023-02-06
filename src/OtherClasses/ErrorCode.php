<?php

namespace App\OtherClasses;

class ErrorCode
{
    public const VALIDATION_ERROR = 'validation_error';
    public const ACCESS_DENIED = 'access_denied';
    public const ENTITY_NOT_FOUND = 'entity_not_found';
    public const UNKNOW_ERROR = 'unknown_error';
    public const BAD_REQUEST = 'bad_request';

    private string $label;

    public function __construct($exceptionClassName)
    {
        switch ($exceptionClassName) {
            case 'AccessDeniedException':
            case 'AccessDeniedHttpException':
                $this->label = self::ACCESS_DENIED;
                break;
            case 'EntityNotFoundException':
            case 'NotFoundHttpException':
                $this->label = self::ENTITY_NOT_FOUND;
                break;
            case 'BadRequestHttpException':
            case 'UploadException':
                $this->label = self::BAD_REQUEST;
                break;
            default:
                $this->label = self::UNKNOW_ERROR;
        }
    }

    public function __toString(): string
    {
        return $this->label;
    }
}
