<?php

namespace WebEtDesign\UserBundle\Subscriber;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use WebEtDesign\UserBundle\Entity\LoginAttempt;

class LoginAttemptSubscriber implements EventSubscriberInterface
{
    public function __construct(private EntityManagerInterface $em) { }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginFailureEvent::class => 'loginFailureEvent',
        ];
    }

    public function loginFailureEvent(LoginFailureEvent $event)
    {
        $request = $event->getRequest();

        $loginAttempt = (new LoginAttempt())
            ->setUsername($request->get('_username'))
            ->setDate(new DateTime('now'))
            ->setFirewall($event->getFirewallName())
            ->setIpAddress($request->getClientIp());

        $this->em->persist($loginAttempt);
        $this->em->flush();
    }
}