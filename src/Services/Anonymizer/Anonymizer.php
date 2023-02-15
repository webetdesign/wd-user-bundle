<?php


namespace WebEtDesign\UserBundle\Services\Anonymizer;


use DateTime;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Inflector\InflectorFactory;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use WebEtDesign\UserBundle\Annotations\Anonymizable;
use WebEtDesign\UserBundle\Annotations\Anonymizer as AnonymizerAnnotation;
use WebEtDesign\UserBundle\Anonymizer\AnonymizerFileInterface;
use WebEtDesign\UserBundle\Event\CustomAnonymizeEvent;
use WebEtDesign\UserBundle\Utils\LoopGuard;

class Anonymizer implements AnonymizerInterface
{
    /**
     * @var AnnotationReader
     */
    protected AnnotationReader $reader;
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $em;

    private LoopGuard $loopGuard;

    /**
     * @var EventDispatcherInterface
     */
    private EventDispatcherInterface $eventDispatcher;

    /**
     * @var array<AnonymizerFileInterface>
     */
    private array $anonymizer = [];

    /**
     * @inheritDoc
     */
    public function __construct(
        EntityManagerInterface $em,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->reader          = new AnnotationReader();
        $this->em              = $em;
        $this->loopGuard       = new LoopGuard();
        $this->eventDispatcher = $eventDispatcher;
    }


    /**
     * @throws \Doctrine\ORM\Mapping\MappingException
     * @throws \ReflectionException
     */
    public function anonimize($object)
    {
        $metadata  = $this->em->getClassMetadata(get_class($object));
        $className = $metadata->rootEntityName;
        if ($this->isAnonymizable($className) && !$this->loopGuard->contains($className,
                $object->getId())) {
            $this->loopGuard->add($className, $object->getId());

            $reflectionClass = $metadata->getReflectionClass();

            foreach ($reflectionClass->getProperties() as $property) {
                /** @var AnonymizerAnnotation $annotation */
                if (($annotation = $this->reader->getPropertyAnnotation($property,
                    AnonymizerAnnotation::class))) {
                    if ($annotation->getType() === AnonymizerAnnotation::TYPE_CUSTOM) {
                        $this->doCustomAnonymize($object, $property, $metadata);
                    } else {
                        if ($metadata->hasField($property->getName()) || $annotation->getType() === AnonymizerAnnotation::TYPE_VICH) {
                            $this->doAnonimize($object, $property, $annotation);
                        }

                        if ($metadata->hasAssociation($property->getName())) {
                            $this->doAssociationAnonimize($object, $property, $annotation,
                                $metadata);
                        }
                    }
                }
            }

            $object->setAnonymizedAt(new DateTime('now'));

            $this->em->persist($object);
        }
    }

    private function doAnonimize(
        $object,
        ReflectionProperty $property,
        AnonymizerAnnotation $annotation
    ) {
        $setter       = 'set' . ucfirst($property->getName());
        $propertyName = $property->getName();
        switch ($annotation->getType()) {
            case AnonymizerAnnotation::TYPE_BOOL_TRUE:
                $value = true;
                break;
            case AnonymizerAnnotation::TYPE_BOOL_FALSE:
                $value = false;
                break;
            case AnonymizerAnnotation::TYPE_NULL:
                $value = null;
                break;
            case AnonymizerAnnotation::TYPE_DATE:
                $value = new DateTime('now');
                break;
            case AnonymizerAnnotation::TYPE_EMAIL:
                $value = 'anonymous-' . uniqid() . '@null.com';
                break;
            case AnonymizerAnnotation::TYPE_UNIQ:
                $value = 'anonymous-' . uniqid();
                break;
            case AnonymizerAnnotation::TYPE_VICH:
                $anonymizer = $this->getAnonymizer(AnonymizerAnnotation::TYPE_VICH);
                if ($anonymizer) {
                    $object = $anonymizer->doAnonymize($object, $property);
                }
                $value = null;
                break;
            case AnonymizerAnnotation::TYPE_STRING:
            default:
                $value = 'anonymous';
                break;
        }

        if (method_exists($object, $setter)) {
            $object->$setter($value);
        } else {
            $object->$propertyName = $value;
        }
    }

    private function doAssociationAnonimize(
        $object,
        ReflectionProperty $property,
        AnonymizerAnnotation $annotation,
        ClassMetadata $metadata
    ) {
        $inflector = InflectorFactory::create()->build();

        $mapping = $metadata->getAssociationMapping($property->getName());
        if ($annotation->getAction() === AnonymizerAnnotation::ACTION_SET_NULL) {
            switch ($mapping['type']) {
                case ClassMetadataInfo::MANY_TO_MANY:
                case ClassMetadataInfo::ONE_TO_MANY:
                    $getter  = 'get' . ucfirst($property->getName());
                    $remover = 'remove' . ucfirst($inflector->singularize($property->getName()));
                    foreach ($object->$getter() as $item) {
                        $object->$remover($item);
                    }
                    break;
                case ClassMetadataInfo::MANY_TO_ONE:
                case ClassMetadataInfo::ONE_TO_ONE:
                    $setter = 'set' . ucfirst($property->getName());
                    $object->$setter(null);
            }
        }
        if ($annotation->getAction() === AnonymizerAnnotation::ACTION_CASCADE) {
            switch ($mapping['type']) {
                case ClassMetadataInfo::MANY_TO_MANY:
                case ClassMetadataInfo::ONE_TO_MANY:
                    $getter = 'get' . ucfirst($property->getName());
                    foreach ($object->$getter() as $item) {
                        $this->anonimize($item);
                    }
                    break;
                case ClassMetadataInfo::MANY_TO_ONE:
                case ClassMetadataInfo::ONE_TO_ONE:
                    $getter = 'get' . ucfirst($property->getName());
                    $child  = $object->$getter();
                    if ($child) {
                        $this->anonimize($child);
                    }
            }
        }
    }

    private function doCustomAnonymize(
        $object,
        ReflectionProperty $property,
        ClassMetadata $metadata
    ) {

        $getter = 'get' . ucfirst($property->getName());
        $setter = 'set' . ucfirst($property->getName());

        $event = new CustomAnonymizeEvent($object, $this->em, $metadata, $property, $getter,
            $setter);

        $this->eventDispatcher->dispatch($event, CustomAnonymizeEvent::NAME);
    }

    private function isAnonymizable(string $className): bool
    {
        $reflectionClass = new ReflectionClass($className);

        return $this->reader->getClassAnnotation($reflectionClass, Anonymizable::class) !== null;
    }

    public function addAnonymizer(AnonymizerFileInterface $file, $key): void
    {
        $this->anonymizer[$key] = $file;
    }

    public function getAnonymizer($key): ?AnonymizerFileInterface
    {
        return $this->anonymizer[$key] ?? null;
    }
}
