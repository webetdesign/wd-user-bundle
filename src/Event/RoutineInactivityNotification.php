<?php


namespace WebEtDesign\UserBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use WebEtDesign\MailerBundle\Attribute\MailEvent;

#[MailEvent(
    name: self::NAME,
    label: 'Routine notification d\'inactivité',
    subject: 'Notification d\'inactivité',
    templateHtml: '@WDUser/emails/ROUTINE_INACTIVITY_NOTIFICATION.html.twig',
    templateText: '@WDUser/emails/ROUTINE_INACTIVITY_NOTIFICATION.txt.twig'
)]
class RoutineInactivityNotification extends Event
{
    public const NAME = 'ROUTINE_INACTIVITY_NOTIFICATION';

    private $user;

    private ?string $ctoLink = null;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getEmail() : ?string {
        return $this->user->getEmail();
    }

    /**
     * @return string
     */
    public function getCtoLink(): ?string
    {
        return $this->ctoLink;
    }

    /**
     * @param ?string $ctoLink
     */
    public function setCtoLink(?string $ctoLink): void
    {
        $this->ctoLink = $ctoLink;
    }
}
