<?php

namespace WebEtDesign\UserBundle\Anonymizer;

use ReflectionProperty;
use Vich\UploaderBundle\Handler\UploadHandler;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;

class AnonymizerVich implements AnonymizerFileInterface
{
    private UploadHandler $uploadHandler;
    private PropertyMappingFactory $mappingFactory;

    public function __construct(UploadHandler $uploadHandler, PropertyMappingFactory $mappingFactory)
    {
        $this->uploadHandler  = $uploadHandler;
        $this->mappingFactory = $mappingFactory;
    }

    /**
     * The Vich mapping is read through Vich's own metadata (attributes,
     * annotations, YAML or XML, as configured), not through docblock
     * annotations only.
     *
     * @param $object
     * @param ReflectionProperty|null $property
     * @return mixed
     */
    public function doAnonymize($object, ?ReflectionProperty $property = null)
    {
        $mapping = $this->mappingFactory->fromField($object, $property->getName());

        if (null === $mapping) {
            throw new \LogicException(sprintf(
                'Property "%s::$%s" is marked for Vich anonymization but has no Vich UploadableField mapping.',
                $object::class,
                $property->getName()
            ));
        }

        $this->uploadHandler->remove($object, $property->getName());
        $setter = 'set' . ucfirst($mapping->getFileNamePropertyName());
        $object->$setter('anonymous_' . uniqid());

        return $object;
    }
}
