<?php

namespace WebEtDesign\UserBundle\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;
use WebEtDesign\UserBundle\Security\Passport\LoginAttemptBadge;

class LoginAttemptException extends AuthenticationException
{
    const DEFAULT_MESSAGE = 'Vous avez tenté de vous connecter trop de fois. Veuillez réessayer plus tard.';

    public function __construct(string $message = self::DEFAULT_MESSAGE, int $code = 0, \Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }

    public function getMessageKey(): string
    {
        return self::DEFAULT_MESSAGE;
    }
}