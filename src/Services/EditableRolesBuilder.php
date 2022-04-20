<?php

declare(strict_types=1);

namespace WebEtDesign\UserBundle\Services;

use Exception;
use Sonata\AdminBundle\Admin\Pool;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class EditableRolesBuilder
{

    protected TokenStorageInterface $tokenStorage;

    protected AuthorizationCheckerInterface $authorizationChecker;

    protected Pool $pool;

    protected TranslatorInterface $translator;

    protected array $rolesHierarchy;

    public function __construct(TokenStorageInterface $tokenStorage, AuthorizationCheckerInterface $authorizationChecker, Pool $pool, array $rolesHierarchy = [])
    {
        $this->tokenStorage         = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
        $this->pool                 = $pool;
        $this->rolesHierarchy       = $rolesHierarchy;
    }

    /**
     * @param TranslatorInterface $translator
     */
    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }

    /**
     * @param string|bool|null $domain
     * @param bool $expanded
     *
     * @return array
     */
    public function getRoles($domain = false, $expanded = true): array
    {
        $roles = [];
        if (!$this->tokenStorage->getToken()) {
            return $roles;
        }

        $this->iterateAdminRoles(function ($role, $isMaster) use (&$roles): void {
            if ($isMaster) {
                $roles[$role] = $role;
            }
        });

        // get roles from the service container
        foreach ($this->rolesHierarchy as $name => $rolesHierarchy) {
            if (!isset($roles[$name])) {
                foreach ($rolesHierarchy as $role) {
                    if (!isset($roles[$role])) {
                        $roles[$role] = $role;
                    }
                }
            }
        }

        return $roles;
    }

    public function getChildrenRoles($role): array
    {
        foreach ($this->rolesHierarchy as $name => $rolesHierarchy) {
            if ($name === $role) {
                return $rolesHierarchy;
            }
        }

        return [];
    }

    /**
     * @param string|bool|null $domain
     *
     * @return array
     */
    public function getRolesReadOnly($domain = false): array
    {
        $rolesReadOnly = [];

        if (!$this->tokenStorage->getToken()) {
            return $rolesReadOnly;
        }

        $this->iterateAdminRoles(function ($role, $isMaster) use ($domain, &$rolesReadOnly): void {
            if (!$isMaster && $this->authorizationChecker->isGranted($role)) {
                $rolesReadOnly[$role] = $role;
            }
        });

        return $rolesReadOnly;
    }

    private function iterateAdminRoles(callable $func): void
    {
        // get roles from the Admin classes
        foreach ($this->pool->getAdminServiceIds() as $id) {
            try {
                $admin = $this->pool->getInstance($id);
            } catch (Exception $e) {
                continue;
            }

            $isMaster        = $admin->isGranted('MASTER');
            $securityHandler = $admin->getSecurityHandler();
            $baseRole        = $securityHandler->getBaseRole($admin);

            if ($baseRole === '') { // the security handler related to the admin does not provide a valid string
                continue;
            }

            foreach ($admin->getSecurityInformation() as $role => $permissions) {
                $role = sprintf($baseRole, $role);
                $func($role, $isMaster, $permissions);
            }
        }
    }

    /**
     * @param string $role
     * @param string|bool|null $domain
     *
     * @return string
     */
    private function translateRole(string $role, $domain): string
    {
        // translation domain is false, do not translate it,
        // null is fallback to message domain
        if (false === $domain || !isset($this->translator)) {
            return $role;
        }

        return $this->translator->trans($role, [], $domain);
    }
}
