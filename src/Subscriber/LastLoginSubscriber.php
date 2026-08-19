<?php

namespace WebEtDesign\UserBundle\Subscriber;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use WebEtDesign\UserBundle\Entity\WDUser;

/**
 * Renseigne lastLogin quel que soit le firewall et l'authenticator.
 *
 * Le champ n'était mis à jour que par les authenticators Azure, via AuthUserHelper::updateLastLogin().
 * Les connexions par mot de passe ne le renseignaient pas, ce qui rend inexploitables les routines
 * RGPD qui s'appuient dessus (rgpd:inactive-user filtre sur lastLogin, et la comparaison SQL exclut
 * les NULL : ces comptes n'étaient donc jamais ni notifiés ni anonymisés).
 */
class LastLoginSubscriber implements EventSubscriberInterface
{
    /**
     * Un firewall stateless (JWT) rejoue une authentification complète à chaque requête, et donc
     * dispatche LoginSuccessEvent à chaque requête. Sans ce palier, chaque appel API provoquerait
     * une écriture en base. La granularité utile pour les routines RGPD se compte en mois.
     */
    private const REFRESH_INTERVAL = '-1 hour';

    public function __construct(private EntityManagerInterface $em) { }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof WDUser) {
            return;
        }

        $lastLogin = $user->getLastLogin();

        if (null !== $lastLogin && $lastLogin > new DateTime(self::REFRESH_INTERVAL)) {
            return;
        }

        $user->setLastLogin(new DateTime());
        $this->em->flush();
    }
}
