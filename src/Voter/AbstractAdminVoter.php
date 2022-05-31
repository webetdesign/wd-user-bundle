<?php

namespace WebEtDesign\UserBundle\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

abstract class AbstractAdminVoter implements VoterInterface
{
    public const VIEW   = 'VIEW';
    public const EDIT   = 'EDIT';
    public const DELETE = 'DELETE';
    public const CREATE = 'CREATE';
    public const LIST   = 'LIST';
    public const EXPORT = 'EXPORT';

    protected Security $security;

    protected array $customAttributes = [];

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function supportsClass($class): bool
    {
        $supportedClass = $this->getSupportedClass();

        if (is_string($class) || !$class) {
            return $class === $supportedClass;
        }

        return $supportedClass === get_class($class) || is_subclass_of(get_class($class), $supportedClass);
    }

    public function supportsAttribute($attribute)
    {
        return in_array($attribute, array_merge([
            self::VIEW,
            self::EDIT,
            self::DELETE,
            self::CREATE,
            self::LIST,
            self::EXPORT,
        ], $this->customAttributes), true);
    }

    public function vote(TokenInterface $token, $entity, array $attributes): int
    {
        /** @var UserInterface $user */
        $user = $token->getUser();

        if ($user && !$user instanceof UserInterface) {
            return VoterInterface::ACCESS_DENIED;
        }

        $attribute = $attributes[0];
        
        if (!$this->supportsClass($entity) || !$this->supportsAttribute($attribute)) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        if (!$this->security->isGranted('ROLE_ADMIN') && !$this->security->isGranted($this->getPrefixRole() . '_' . $attribute)) {
            return VoterInterface::ACCESS_DENIED;
        }


        return is_string($entity) ? $this->voteOnClassName($token, $entity, $attributes) : $this->voteOnEntity($token,
            $entity, $attributes);
    }

    protected function voteOnClassName(TokenInterface $token, $className, array $attributes): int
    {
        return VoterInterface::ACCESS_GRANTED;
    }

    protected function voteOnEntity(TokenInterface $token, $entity, array $attributes): int
    {
        return VoterInterface::ACCESS_GRANTED;
    }

    abstract public function getPrefixRole(): string;

    abstract public function getSupportedClass(): string;
}
