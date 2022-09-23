<?php


namespace WebEtDesign\UserBundle\Event\Mail;


use Symfony\Component\HttpFoundation\File\File;
use WebEtDesign\MailerBundle\Attribute\MailEvent;
use WebEtDesign\MailerBundle\Event\AbstractMailEvent;
use WebEtDesign\UserBundle\Controller\User\ResettingController;
use Symfony\Contracts\EventDispatcher\Event;
use WebEtDesign\UserBundle\Entity\WDUser;

#[MailEvent(name: self::RESETTING_EVENT, label: 'Mot de passe oublié (front)')]
class ResettingEvent extends AbstractMailEvent
{
    const RESETTING_EVENT = 'RESETTING_EVENT';

    public function __construct(private WDUser $user, private string $resetPath) {}

    public function getEmail(): string
    {
        return $this->getUser()->getEmail();
    }

    /**
     * @return WDUser
     */
    public function getUser(): WDUser
    {
        return $this->user;
    }

    /**
     * @return string|null
     */
    public function getResetPath(): ?string
    {
        return $this->resetPath;
    }

    /**
     * @param string|null $resetPath
     * @return ResettingEvent
     */
    public function setResetPath(?string $resetPath): ResettingEvent
    {
        $this->resetPath = $resetPath;
        return $this;
    }

    public function getFile(): null|array|File{
        return null;
    }

}
