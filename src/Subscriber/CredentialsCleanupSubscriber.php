<?php

declare(strict_types=1);

namespace WebEtDesign\UserBundle\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\AuthenticationTokenCreatedEvent;
use WebEtDesign\UserBundle\Entity\WDUser;

/** Clears transient credentials before authentication success, including manual login. */
final class CredentialsCleanupSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [AuthenticationTokenCreatedEvent::class => ['onAuthenticationTokenCreated', -1024]];
    }

    public function onAuthenticationTokenCreated(AuthenticationTokenCreatedEvent $event): void
    {
        $user = $event->getAuthenticatedToken()->getUser();
        if ($user instanceof WDUser) {
            $user->setPlainPassword(null);
        }
    }
}
