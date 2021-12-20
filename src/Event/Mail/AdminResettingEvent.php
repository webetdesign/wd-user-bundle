<?php


namespace WebEtDesign\UserBundle\Event\Mail;


use WebEtDesign\UserBundle\Controller\User\ResettingController;
use Symfony\Contracts\EventDispatcher\Event;
use WebEtDesign\UserBundle\Entity\WDUser;

class AdminResettingEvent extends Event
{
    const NAME = 'ADMIN_RESETTING_EVENT';

    private WDUser $user;

    private ?string $resetPath = 'admin_reset_password';

    public function __construct(WDUser $user) {
        $this->user = $user;
    }

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
     * @return AdminResettingEvent
     */
    public function setResetPath(?string $resetPath): AdminResettingEvent
    {
        $this->resetPath = $resetPath;
        return $this;
    }

}
