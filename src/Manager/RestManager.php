<?php

namespace App\Manager;

use App\OtherClasses\ErrorCode;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RestManager
{
    public const RETURN_TYPE_JSON = 'RETURN_TYPE_JSON';
    public const RETURN_TYPE_PLAIN = 'RETURN_TYPE_PLAIN';
    public const AUTH_USERPASS = 'AUTH_USERPASS';
    public const AUTH_TOKEN_BEARER = 'AUTH_TOKEN_BEARER';
    public const AUTH_TOKEN = 'AUTH_TOKEN';

    private $returnType;
    private $authType;
    private $user;
    private $password;
    private $token;

    private ValidatorInterface $validator;

    /**
     * RestManager constructor.
     */
    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
        $this->returnType = self::RETURN_TYPE_JSON;
        $this->authType = self::AUTH_TOKEN;
    }

    public function setReturnType($returnType): void
    {
        $this->returnType = $returnType;
    }

    public function setAuthType($authType): void
    {
        $this->authType = $authType;
    }

    /**
     * @param string $token
     */
    public function setToken($token): void
    {
        $this->token = $token;
    }

    /**
     * @param string $user
     */
    public function setUser($user): void
    {
        $this->user = $user;
    }

    /**
     * @param string $password
     */
    public function setPassword($password): void
    {
        $this->password = $password;
    }

    public function request($url, $method = Request::METHOD_POST, $parameters = null, $json = true)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $headers = [];

        switch ($this->authType) {
            case self::AUTH_USERPASS:
                if (null !== $this->user && null !== $this->password) {
                    curl_setopt($ch, CURLOPT_USERPWD, $this->user.':'.$this->password);
                }
                break;

            case self::AUTH_TOKEN_BEARER:
                if ($this->token) {
                    $headers[] = 'Authorization: Bearer '.$this->token;
                }
                break;

            case self::AUTH_TOKEN:
            default:
                if ($this->token) {
                    $query = parse_url($url, PHP_URL_QUERY);
                    if ($query) {
                        $url .= '&access_token='.$this->token;
                    } else {
                        $url .= '?access_token='.$this->token;
                    }
                }
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                break;
        }

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        if (Request::METHOD_POST === $method) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, Request::METHOD_POST);
        } elseif (Request::METHOD_PUT === $method) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, Request::METHOD_PUT);
        }

        if (null !== $parameters) {
            if ($json) {
                $parameters = json_encode($parameters);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
                $headers[] = 'Content-Type: application/json';
                $headers[] = 'Content-Length: '.strlen($parameters);
            } else {
                $strPost = '';
                foreach ($parameters as $key => $value) {
                    $strPost .= '&'.$key.'='.$value;
                }
                $strPost = substr($strPost, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $strPost);
                $headers[] = 'Content-Type: text/plain';
                $headers[] = 'Content-Length: '.strlen($strPost);
            }
        }
        if (count($headers) > 0) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $rep = curl_exec($ch);

        if (false === $rep) {
            throw new \RuntimeException(curl_error($ch));
        }

        if (self::RETURN_TYPE_JSON === $this->returnType) {
            $rep = json_decode($rep);
        }

        return $rep;
    }

    /**
     * @param mixed                   $value       The value to validate
     * @param Constraint|Constraint[] $constraints The constraint(s) to validate against
     * @param string|GroupSequence|(string|GroupSequence)[]|null $groups      The validation groups to validate. If none is given, "Default" is assumed
     */
    public function getJsonValidationError($value, $constraints = null, $groups = null): ?array
    {
        $errors = $this->validator->validate($value, $constraints, $groups);

        if (0 !== count($errors)) {
            $arRet = [];
            foreach ($errors as $error) {
                $arRet[$error->getPropertyPath()] = $error->getMessage();
            }

            return $this->getErrorsToJson($arRet);
        }

        return null;
    }

    public function getErrorsToJson($errors)
    {
        return [
            'error' => [
                'message' => 'There was a validation error',
                'status' => Response::HTTP_BAD_REQUEST,
                'code' => ErrorCode::VALIDATION_ERROR,
                'details' => $errors,
            ],
        ];
    }
}
