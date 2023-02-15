<?php

namespace WebEtDesign\UserBundle\Anonymizer;

use Doctrine\Common\Annotations\AnnotationReader;
use ReflectionProperty;
use Vich\UploaderBundle\Handler\UploadHandler;
use Vich\UploaderBundle\Mapping\Annotation\UploadableField;

class AnonymizerVich implements AnonymizerFileInterface
{
    private UploadHandler $uploadHandler;
    private AnnotationReader $reader;

    /**
     * AnonymizerVich constructor.
     * @param UploadHandler $uploadHandler
     */
    public function __construct(UploadHandler $uploadHandler)
    {
        $this->uploadHandler = $uploadHandler;
        $this->reader       = new AnnotationReader();
    }

    /**
     * @param $object
     * @param ReflectionProperty|null $property
     * @return mixed
     */
    public function doAnonymize($object, ?ReflectionProperty $property = null)
    {
        /** @var UploadableField $annotation */
        $annotation = $this->reader->getPropertyAnnotation($property, UploadableField::class);
        $this->uploadHandler->remove($object, $property->getName());
        $setter = 'set' . ucfirst($annotation->getFileNameProperty());
        $object->$setter('anonymous_' . uniqid());
        return $object;
    }
}
