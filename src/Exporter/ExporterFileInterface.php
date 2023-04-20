<?php


namespace WebEtDesign\UserBundle\Exporter;


use ReflectionProperty;

interface ExporterFileInterface
{
    public function doExport(string $tmpDir, $object, ?ReflectionProperty $property = null);
}
