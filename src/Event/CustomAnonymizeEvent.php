<?php


namespace WebEtDesign\UserBundle\Event;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use ReflectionProperty;
use Symfony\Contracts\EventDispatcher\Event;

class CustomAnonymizeEvent extends Event
{

    public const NAME = 'CUSTOM_ANONYMIZE_EVENT';

    private object $object;

    private EntityManagerInterface $entityManager;

    private ClassMetadata $metadata;

    private ReflectionProperty $property;

    private string $getter;

    private string $setter;


    public function __construct(
        object $object,
        EntityManagerInterface $entityManager,
        ClassMetadata $metadata,
        ReflectionProperty $property,
        string $getter,
        string $setter
    ) {
        $this->object = $object;
        $this->entityManager = $entityManager;
        $this->metadata = $metadata;
        $this->property = $property;
        $this->getter = $getter;
        $this->setter = $setter;
    }

    /**
     * @return object
     */
    public function getObject(): object
    {
        return $this->object;
    }

    /**
     * @return EntityManagerInterface
     */
    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    /**
     * @return ClassMetadata
     */
    public function getMetadata(): ClassMetadata
    {
        return $this->metadata;
    }

    /**
     * @return ReflectionProperty
     */
    public function getProperty(): ReflectionProperty
    {
        return $this->property;
    }

    /**
     * @return string
     */
    public function getGetter(): string
    {
        return $this->getter;
    }

    /**
     * @return string
     */
    public function getSetter(): string
    {
        return $this->setter;
    }
}
