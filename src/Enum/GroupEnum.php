<?php
declare(strict_types=1);

namespace WebEtDesign\UserBundle\Enum;

enum GroupEnum: string
{
    case ADMIN = 'ADMIN';
    case USER = 'USER';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrateur',
            self::USER  => 'Utilisateur',
        };
    }

    public function roles(): array
    {
        return match ($this) {
            self::ADMIN => ['ROLE_ADMIN'],
            self::USER  => ['ROLE_USER'],
        };
    }
}
