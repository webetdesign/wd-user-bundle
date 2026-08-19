<?php

namespace WebEtDesign\UserBundle\Subscriber;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
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
        private LoginAttemptRepository $loginAttemptRepository,
        private RequestStack $requestStack
    ) { }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginFailureEvent::class => 'loginFailure',
            LoginSuccessEvent::class => 'loginSuccess',
            // Priorité alignée sur LoginThrottlingListener de Symfony : la limitation doit passer
            // AVANT CheckCredentialsListener (priorité 0). Enregistré après lui à priorité égale,
            // le contrôle ne s'exécutait que si le mot de passe était bon — un attaquant gardait
            // donc un nombre illimité d'essais, seule la réussite finale était bloquée.
            CheckPassportEvent::class => ['checkPassport', 2048]
        ];
    }

    // Remove LoginAttempt on login success
    public function loginSuccess(LoginSuccessEvent $event)
    {
        $request = $event->getRequest();

        // Même résolution que loginFailure() : sans quoi les tentatives enregistrées sous
        // l'identifiant du passeport ne seraient jamais purgées, et un client légitime resterait
        // bloqué après ses premiers échecs.
        $loginAttempts = $this->loginAttemptRepository->findBy(['username' => $this->resolveUsername($event->getPassport(), $request), 'ipAddress' => $request->getClientIp()]);
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
            ->setUsername($this->resolveUsername($event->getPassport(), $request))
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
        $request  = $this->requestStack->getCurrentRequest();

        $badge = $passport->hasBadge(LoginAttemptBadge::class)
            ? $passport->getBadge(LoginAttemptBadge::class)
            : null;

        if ($badge instanceof LoginAttemptBadge && $badge->isResolved()) {
            return;
        }

        // Hors badge explicite, seules les authentifications par mot de passe sont limitées : le
        // form_login natif de Symfony et le json_login n'ajoutent aucun badge — leurs firewalls
        // restaient donc sans limitation — tandis qu'un jeton JWT ou un retour Azure n'ont pas à
        // être comptabilisés comme des essais.
        if (!$badge instanceof LoginAttemptBadge && !$passport->hasBadge(PasswordCredentials::class)) {
            return;
        }

        // Même identifiant que celui sous lequel l'échec sera enregistré, sans quoi le comptage
        // porterait sur une autre clé que l'enregistrement — le badge du form_login admin transporte
        // par exemple le champ POST brut, alors que le UserBadge en porte la version trim().
        $ip       = $badge?->getIp() ?? $request?->getClientIp();
        $username = $this->resolveUsername($passport, $request) ?? $badge?->getUsername();

        if (null === $ip || null === $username || '' === $username) {
            return;
        }

        $delay = $this->parameterBag->get('wd_user.security.delay');
        $maxAttempts = $this->parameterBag->get('wd_user.security.max_attempts');
        $since = new DateTime('now -' . $delay . 'seconds');

        if ($maxAttempts <= $this->loginAttemptRepository->countAttemptSince($ip, $username, $since)) {
            throw new LoginAttemptException();
        }

        $badge?->markResolved();
    }

    /**
     * L'échec était enregistré sous le seul paramètre POST _username, alors que le comptage lit
     * l'identifiant porté par le UserBadge. Les deux coïncident sur un form_login, mais pas sur un
     * json_login — dont le corps est du JSON : les tentatives y étaient enregistrées sous NULL et
     * n'étaient jamais recomptées, donc jamais limitées.
     */
    private function resolveUsername(?Passport $passport, ?Request $request): ?string
    {
        if ($passport?->hasBadge(UserBadge::class)) {
            /** @var UserBadge $userBadge */
            $userBadge = $passport->getBadge(UserBadge::class);

            return $userBadge->getUserIdentifier();
        }

        return $request?->request->get('_username');
    }
}
