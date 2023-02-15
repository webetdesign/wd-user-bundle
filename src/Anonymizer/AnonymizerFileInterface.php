<?php

namespace WebEtDesign\UserBundle\Anonymizer;

use ReflectionProperty;

interface AnonymizerFileInterface
{
    /**
     * @param $object
     * @param ReflectionProperty|null $property
     * @return mixed
     */
    public function doAnonymize($object, ?ReflectionProperty $property = null);
}
