<?php

namespace WebEtDesign\UserBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AdminRolesTwigExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('role', [$this, 'getRole']),
            new TwigFilter('permission', [$this, 'getPermission']),
        ];
    }

    public function getRole($value): string
    {
        $name = explode('_', $value);
        unset($name[count($name) - 1]);

        return implode('_', $name);
    }

    public function getPermission($value): string
    {
        $name = explode('_', $value);

        return $name[count($name) - 1];
    }
}
