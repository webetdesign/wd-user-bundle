<?php


namespace WebEtDesign\UserBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use WebEtDesign\MailerBundle\Attribute\MailEvent;

#[MailEvent(
    name: self::NAME,
    label: 'Routine rappel mise à jour mot de passe obsolète',
    subject: 'Mise à jour mot de passe obsolète',
    templateHtml: '@WDUser/emails/ROUTINE_CHANGE_OLD_PASSWORD_REMINDER.html.twig',
    templateText: '@WDUser/emails/ROUTINE_CHANGE_OLD_PASSWORD_REMINDER.txt.twig'
)]
class RoutineChangeOldPasswordReminder extends Event
{
    public const NAME = 'ROUTINE_CHANGE_OLD_PASSWORD_REMINDER';

    private $user;

    private ?string $resetLink;

    private string $useTime;

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
    public function getResetLink(): ?string
    {
        return $this->resetLink;
    }

    /**
     * @param ?string $resetLink
     */
    public function setResetLink(?string $resetLink): void
    {
        $this->resetLink = $resetLink;
    }

    /**
     * @return string
     */
    public function getUseTime(): string
    {
        return $this->useTime;
    }

    /**
     * @param string $useTime
     */
    public function setUseTime(string $useTime): void
    {
        $this->useTime = $useTime;
    }
}
