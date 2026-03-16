<?php

namespace WebEtDesign\UserBundle\Anonymizer;

use ReflectionProperty;
use Vich\UploaderBundle\Handler\UploadHandler;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;

class AnonymizerVich implements AnonymizerFileInterface
{
    private UploadHandler $uploadHandler;
    private PropertyMappingFactory $propertyMappingFactory;

    /**
     * AnonymizerVich constructor.
     * @param UploadHandler $uploadHandler
     */
    public function __construct(UploadHandler $uploadHandler, PropertyMappingFactory $propertyMappingFactory)
    {
        $this->uploadHandler = $uploadHandler;
        $this->propertyMappingFactory = $propertyMappingFactory;
    }

    /**
     * @param $object
     * @param ReflectionProperty|null $property
     * @return mixed
     */
    public function doAnonymize($object, ?ReflectionProperty $property = null): mixed
    {
        if ($property === null) {
            return $object;
        }

        $mapping = $this->propertyMappingFactory->fromField($object, $property->getName());
        if ($mapping === null) {
            return $object;
        }

        $this->uploadHandler->remove($object, $property->getName());
        $mapping->setFileName($object, 'anonymous_' . uniqid());

        return $object;
    }
}
