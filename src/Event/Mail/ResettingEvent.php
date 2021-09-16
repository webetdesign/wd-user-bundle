<?php


namespace WebEtDesign\UserBundle\Event\Mail;


use WebEtDesign\UserBundle\Controller\User\ResettingController;
use Symfony\Contracts\EventDispatcher\Event;
use WebEtDesign\UserBundle\Entity\WDUser;

class ResettingEvent extends Event
{
    const NAME = 'RESETTING_EVENT';

    private WDUser $user;

    private ?string $resetPath = ResettingController::ROUTE_RESETTING;

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
     * @return ResettingEvent
     */
    public function setResetPath(?string $resetPath): ResettingEvent
    {
        $this->resetPath = $resetPath;
        return $this;
    }

}
