<?php


namespace WebEtDesign\UserBundle\Exporter;


use ReflectionProperty;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;
use Vich\UploaderBundle\Storage\StorageInterface;

class ExporterVich implements ExporterFileInterface
{
    private PropertyMappingFactory $propertyMappingFactory;
    private StorageInterface $storage;

    /**
     * @inheritDoc
     */
    public function __construct(PropertyMappingFactory $propertyMappingFactory, StorageInterface $storage)
    {
        $this->propertyMappingFactory = $propertyMappingFactory;
        $this->storage = $storage;
    }


    public function doExport(
        string $tmpDir,
        $object,
        ?ReflectionProperty $property = null
    ) {
        if ($property === null) {
            return null;
        }

        $mapping = $this->propertyMappingFactory->fromField($object, $property->getName());

        if ($mapping === null) {
            return null;
        }

        $fileName = $mapping->getFileName($object);
        if (empty($fileName)) {
            return null;
        }

        $stream = $this->storage->resolveStream($object, $property->getName());
        if ($stream === null) {
            return null;
        }

        try {
            $newPath = $tmpDir . '/' . $fileName;
            $destination = fopen($newPath, 'wb');
            if ($destination === false) {
                if (is_resource($stream)) {
                    fclose($stream);
                }
                return [
                    'file' => null,
                ];
            }

            stream_copy_to_stream($stream, $destination);
            fclose($destination);
            if (is_resource($stream)) {
                fclose($stream);
            }
        } catch (\Throwable $e) {
            if (is_resource($stream)) {
                fclose($stream);
            }
            return [
                'file' => null
            ];
        }

        return [
            'file' => $fileName
        ];
    }
}
