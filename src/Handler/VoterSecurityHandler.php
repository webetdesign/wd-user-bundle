<?php
/**
 * Created by PhpStorm.
 * User: jvaldena
 * Date: 16/01/2020
 * Time: 17:38
 */

namespace WebEtDesign\UserBundle\Handler;

use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Security\Handler\RoleSecurityHandler;
use Sonata\AdminBundle\Security\Handler\SecurityHandlerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

class VoterSecurityHandler implements SecurityHandlerInterface
{

    /**
     * @param string|string[] $superAdminRoles
     */
    public function __construct(private AuthorizationCheckerInterface $authorizationChecker, private ?array $superAdminRoles = [])
    {

        // NEXT_MAJOR: Keep only the elseif part and add typehint.
        if (\is_array($superAdminRoles)) {
            @trigger_error(sprintf(
                'Passing an array as argument 1 of "%s()" is deprecated since sonata-project/admin-bundle 4.6'
                .' and will throw an error in 5.0. You MUST pass a string instead.',
                __METHOD__
            ), \E_USER_DEPRECATED);

            $this->superAdminRoles = $superAdminRoles;
        } elseif (\is_string($superAdminRoles)) {
            $this->superAdminRoles = [$superAdminRoles];
        } else {
            throw new \TypeError(sprintf(
                'Argument 1 passed to "%s()" must be of type "array" or "string", %s given.',
                __METHOD__,
                \is_object($superAdminRoles) ? 'instance of "'.\get_class($superAdminRoles).'"' : '"'.\gettype($superAdminRoles).'"'
            ));
        }
    }
    /**
     * {@inheritdoc}
     */
    public function isGranted(AdminInterface $admin, $attributes, $object = null): bool
    {
        if (!\is_array($attributes)) {
            $attributes = [$attributes];
        }

        $useAll = false;
        foreach ($attributes as $pos => $attribute) {
            // If the attribute is not already a ROLE_ we generate the related role.
            if (!str_starts_with($attribute, 'ROLE_')) {
                $attributes[$pos] = sprintf($this->getBaseRole($admin), $attribute);
                // All the admin related role are available when you have the `_ALL` role.
                $useAll = true;
            }
        }

        $allRole = sprintf($this->getBaseRole($admin), 'ALL');

        try {
            return $this->isAnyGranted($this->superAdminRoles)
                || $this->isAnyGranted($attributes, $object)
                || $useAll && $this->isAnyGranted([$allRole], $object);
        } catch (AuthenticationCredentialsNotFoundException $e) {
            return false;
        }

    }

    public function getBaseRole(AdminInterface $admin): string
    {
        return sprintf('ROLE_%s_%%s', str_replace('.', '_', strtoupper($admin->getCode())));
    }

    public function buildSecurityInformation(AdminInterface $admin): array
    {
        return [];
    }

    private function isAnyGranted(array $attributes, $subject = null): bool
    {
        foreach ($attributes as $attribute) {
            if ($this->authorizationChecker->isGranted($attribute, $subject)) {
                return true;
            }
        }

        return false;
    }

    public function createObjectSecurity(AdminInterface $admin, object $object): void
    {
        // TODO: Implement createObjectSecurity() method.
    }

    public function deleteObjectSecurity(AdminInterface $admin, object $object): void
    {
        // TODO: Implement deleteObjectSecurity() method.
    }
}
