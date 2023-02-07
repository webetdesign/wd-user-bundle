<?php
declare(strict_types=1);

namespace WebEtDesign\UserBundle\Services;

use App\Entity\User\User;
use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class AuthUserHelper
{
    public function __construct(protected EntityManagerInterface $em, protected ParameterBagInterface $parameterBag) { }

    public function createUserFromAzure(ResourceOwnerInterface $resourceOwner, string $clientName): User
    {
        $config = $this->getConfig($clientName);

        $userClass = $this->parameterBag->get('wd_user.user.class');

        $user = new $userClass();

        $user->setAzureId($resourceOwner->getId());
        $user->setEmail($resourceOwner->toArray()['email'])
            ->setUsername($resourceOwner->toArray()['email'])
            ->setEnabled(true)
            ->setFirstname($resourceOwner->getFirstName())
            ->setLastname($resourceOwner->getFirstName())
            ->setNewsletter(false)
            ->setPermissions($config['roles']);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function updateAzureId(User $user, $id): User
    {
        $user->setAzureId($id);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    protected function getConfig(string $clientName)
    {
        $clientConfigs = $this->parameterBag->get('smity_user.azure_clients');

        foreach ($clientConfigs as $config) {
            if ($config['client_name'] === $clientName) {
                return $config;
            }
        }

        throw new AuthenticationException("No azure configuration found for client_name '$clientName' in smity_user.azure_directory.clients");
    }
}
