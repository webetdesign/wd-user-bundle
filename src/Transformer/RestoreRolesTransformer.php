<?php

declare(strict_types=1);

namespace WebEtDesign\UserBundle\Transformer;

use RuntimeException;
use Symfony\Component\Form\DataTransformerInterface;
use WebEtDesign\UserBundle\Services\EditableRolesBuilder;

class RestoreRolesTransformer implements DataTransformerInterface
{

    protected ?array $originalRoles = null;

    protected EditableRolesBuilder $rolesBuilder;

    public function __construct(EditableRolesBuilder $rolesBuilder)
    {
        $this->rolesBuilder = $rolesBuilder;
    }

    public function setOriginalRoles(array $originalRoles = null): void
    {
        $this->originalRoles = $originalRoles ?: [];
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value): mixed
    {
        if (null === $value) {
            return null;
        }

        if (null === $this->originalRoles) {
            throw new RuntimeException('Invalid state, originalRoles array is not set');
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($selectedRoles): ?array
    {
        if (null === $this->originalRoles) {
            throw new RuntimeException('Invalid state, originalRoles array is not set');
        }

        $availableRoles = $this->rolesBuilder->getRoles();

        $hiddenRoles = array_diff($this->originalRoles, array_keys($availableRoles));

        return array_merge($selectedRoles, $hiddenRoles);
    }
}
