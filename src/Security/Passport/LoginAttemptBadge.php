<?php

namespace WebEtDesign\UserBundle\Security\Passport;

use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;

class LoginAttemptBadge implements BadgeInterface
{
    private bool $resolved = false;

    public function __construct(private string $ip, private string $username) {}

    public function getIp(): string
    {
        return $this->ip;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function markResolved(): void
    {
        $this->resolved = true;
    }

    public function isResolved(): bool
    {
        return $this->resolved;
    }
}