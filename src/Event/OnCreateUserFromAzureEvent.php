<?php

namespace WebEtDesign\UserBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use WebEtDesign\UserBundle\Entity\WDUser;

class OnCreateUserFromAzureEvent extends Event
{
    const NAME = 'ON_CREATE_USER_FROM_AZURE';

    private WDUser $user;
    private array  $azureData;
    private string $context;

    public function __construct(string $context, WDUser $user, array $azureData)
    {
        $this->user      = $user;
        $this->azureData = $azureData;
        $this->context   = $context;
    }

    /**
     * @param WDUser $user
     * @return OnCreateUserFromAzureEvent
     */
    public function setUser(WDUser $user): OnCreateUserFromAzureEvent
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return WDUser
     */
    public function getUser(): WDUser
    {
        return $this->user;
    }

    /**
     * @param array $azureData
     * @return OnCreateUserFromAzureEvent
     */
    public function setAzureData(array $azureData): OnCreateUserFromAzureEvent
    {
        $this->azureData = $azureData;
        return $this;
    }

    /**
     * @return array
     */
    public function getAzureData(): array
    {
        return $this->azureData;
    }

    /**
     * @param string $context
     * @return OnCreateUserFromAzureEvent
     */
    public function setContext(string $context): OnCreateUserFromAzureEvent
    {
        $this->context = $context;
        return $this;
    }

    /**
     * @return string
     */
    public function getContext(): string
    {
        return $this->context;
    }
}
