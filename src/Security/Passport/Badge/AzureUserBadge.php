<?php

namespace WebEtDesign\UserBundle\Security\Passport\Badge;

use App\Entity\User\User;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\EventListener\UserProviderListener;
use WebEtDesign\UserBundle\Services\AuthUserHelper;

class AzureUserBadge extends UserBadge
{

    private ?UserInterface         $user = null;
    private ResourceOwnerInterface $resourceOwner;
    private AuthUserHelper         $userHelper;
    private string                 $clientName;

    public function __construct(
        ResourceOwnerInterface $resourceOwner,
        callable $userLoader,
        AuthUserHelper $userHelper,
        string $clientName
    ) {
        parent::__construct($resourceOwner->getId(), $userLoader);
        $this->resourceOwner = $resourceOwner;
        $this->userHelper    = $userHelper;
        $this->clientName    = $clientName;
    }

    public function getUser(): UserInterface
    {
        if (null !== $this->user) {
            return $this->user;
        }

        if (null === $this->getUserLoader()) {
            throw new \LogicException(sprintf('No user loader is configured, did you forget to register the "%s" listener?',
                UserProviderListener::class));
        }

        try {
            // Search user by azureId
            $user = ($this->getUserLoader())($this->getUserIdentifier());
        } catch (UserNotFoundException $exception) {
            try {
                // Search user by email
                $user = ($this->getUserLoader())($this->resourceOwner->toArray()['email']);
                $this->userHelper->updateAzureId($user, $this->resourceOwner->getId());
            } catch (UserNotFoundException $exception) {
                $user = $this->userHelper->createUserFromAzure($this->resourceOwner, $this->clientName);
            }
        }

        if (!$user instanceof UserInterface) {
            throw new AuthenticationServiceException(sprintf('The user provider must return a UserInterface object, "%s" given.',
                get_debug_type($user)));
        }

        return $this->user = $user;
    }


    public function isResolved(): bool
    {
        return true;
    }
}
