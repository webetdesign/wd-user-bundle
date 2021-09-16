<?php

namespace  WebEtDesign\UserBundle\Security;

use Symfony\Component\Security\Core\Exception\AccountExpiredException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use WebEtDesign\UserBundle\Entity\WDUser;

class AdminUserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user)
    {
//        dump(get_class($user));die;

        if (!$user instanceof WDUser) {
            return;
        }
    }

    public function checkPostAuth(UserInterface $user)
    {
//        dump(get_class($user));die;

        if (!$user instanceof WDUser) {
            return;
        }
    }
}
