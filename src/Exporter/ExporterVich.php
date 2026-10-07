<?php


namespace WebEtDesign\UserBundle\Exporter;


use ReflectionProperty;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class ExporterVich implements ExporterFileInterface
{
    /**
     * @var ParameterBagInterface
     */
    private ParameterBagInterface $parameterBag;
    /**
     * @var ?UploaderHelper
     */
    private ?UploaderHelper $vichHelper;
    /**
     * @var ?PropertyMappingFactory
     */
    private ?PropertyMappingFactory $mappingFactory;

    /**
     * @inheritDoc
     */
    public function __construct(
        ParameterBagInterface $parameterBag,
        ?UploaderHelper $vichHelper = null,
        ?PropertyMappingFactory $mappingFactory = null
    ) {
        $this->parameterBag   = $parameterBag;
        $this->vichHelper     = $vichHelper;
        $this->mappingFactory = $mappingFactory;
    }


    /**
     * The Vich mapping is read through Vich's own metadata (attributes,
     * annotations, YAML or XML, as configured), not through docblock
     * annotations only.
     */
    public function doExport(
        string $tmpDir,
        $object,
        ?ReflectionProperty $property = null
    ) {
        if ($this->vichHelper === null || $this->mappingFactory === null) {
            return null;
        }

        $mapping = $this->mappingFactory->fromField($object, $property->getName());
        $imgPath = $this->vichHelper->asset($object, $property->getName());

        if ($imgPath === null || $mapping === null) {
            return null;
        }

       try{
           $publicDir = $this->parameterBag->get('kernel.project_dir') . '/public';
           $getter    = 'get' . ucfirst($mapping->getFileNamePropertyName());

           $path    = $publicDir . $imgPath;
           $newPath = $tmpDir . '/' . $object->$getter();

           copy($path, $newPath);
       }catch (\Exception $e){
            return [
                'file' => null
            ];
       }

        return [
            'file' => $object->$getter()
        ];
    }
}
