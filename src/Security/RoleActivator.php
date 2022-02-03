<?php

namespace WebEtDesign\UserBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Translation\Bundle\EditInPlace\ActivatorInterface;

class RoleActivator implements ActivatorInterface
{
    public function __construct(private AuthorizationCheckerInterface $authorizationChecker)
    {}

    /**
     * {@inheritdoc}
     */
    public function checkRequest(Request $request = null): bool
    {
        try {
            return $this->authorizationChecker->isGranted('ROLE_TRANSLATE');
        } catch (AuthenticationCredentialsNotFoundException $e) {
            return false;
        }
    }
}
