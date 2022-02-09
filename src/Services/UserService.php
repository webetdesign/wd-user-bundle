<?php

namespace WebEtDesign\UserBundle\Services;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use WebEtDesign\UserBundle\Event\OnCreateUserFromAzureEvent;

class UserService
{

    private string                   $userClass;
    private ParameterBagInterface    $parameterBag;
    private EventDispatcherInterface $eventDispatcher;
    private EntityManagerInterface   $em;

    public function __construct(
        EntityManagerInterface $em,
        ParameterBagInterface $parameterBag,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->em = $em;
        $this->parameterBag = $parameterBag;
        $this->eventDispatcher = $eventDispatcher;

        $this->userClass = $this->parameterBag->get('wd_user.user.class');
    }

    public function createUser(array $azureUser, string $azureId, array $roles)
    {
        $user = new $this->userClass();

        $user->setAzureId($azureId);

        $name = explode(" ", $azureUser['name']);
        $user->setFirstname($name[0]);
        if (sizeof($name) == 2) {
            $user->setLastname($name[1]);
        }

        $user->setEmail($azureUser['email']);
        $user->setUsername($azureUser['email']);
        $user->setEnabled(true);
        $user->setPermissions($roles);

        // Subscribe to this event to add other data to user.
        $event = new OnCreateUserFromAzureEvent('admin', $user, $azureUser);
        $this->eventDispatcher->dispatch($event, OnCreateUserFromAzureEvent::NAME);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

}
