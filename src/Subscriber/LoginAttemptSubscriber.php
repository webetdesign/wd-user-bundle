<?php

namespace WebEtDesign\UserBundle\Subscriber;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use WebEtDesign\UserBundle\Entity\LoginAttempt;
use WebEtDesign\UserBundle\Exception\LoginAttemptException;
use WebEtDesign\UserBundle\Repository\LoginAttemptRepository;
use WebEtDesign\UserBundle\Security\Passport\LoginAttemptBadge;

class LoginAttemptSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private ParameterBagInterface $parameterBag,
        private LoginAttemptRepository $loginAttemptRepository
    ) { }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginFailureEvent::class => 'loginFailure',
            LoginSuccessEvent::class => 'loginSuccess',
            CheckPassportEvent::class => 'checkPassport'
        ];
    }

    // Remove LoginAttempt on login success
    public function loginSuccess(LoginSuccessEvent $event)
    {
        $request = $event->getRequest();

        $loginAttempts = $this->loginAttemptRepository->findBy(['username' => $request->get('_username'), 'ipAddress' => $request->getClientIp()]);
        foreach ($loginAttempts as $loginAttempt) {
            $this->em->remove($loginAttempt);
        }

        $this->em->flush();
    }

    // Save login attempt on login failure
    public function loginFailure(LoginFailureEvent $event)
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

    // Verify user can try login
    public function checkPassport(CheckPassportEvent $event): void
    {
        $passport = $event->getPassport();
        if (!$passport->hasBadge(LoginAttemptBadge::class)) {
            return;
        }

        /** @var LoginAttemptBadge $badge */
        $badge = $passport->getBadge(LoginAttemptBadge::class);
        if ($badge->isResolved()) {
            return;
        }

        $delay = $this->parameterBag->get('wd_user.security.delay');
        $maxAttempts = $this->parameterBag->get('wd_user.security.max_attempts');
        $since = new DateTime('now -' . $delay . 'seconds');

        if ($maxAttempts < $this->loginAttemptRepository->countAttemptSince($badge->getIp(), $badge->getUsername(), $since)) {
            throw new LoginAttemptException();
        }

        $badge->markResolved();
    }
}