<?php


namespace WebEtDesign\UserBundle\Exporter;


use Doctrine\Common\Annotations\AnnotationReader;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Vich\UploaderBundle\Mapping\Annotation\UploadableField;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class ExporterVich implements ExporterFileInterface
{
    /**
     * @var AnnotationReader
     */
    protected AnnotationReader $reader;
    /**
     * @var ParameterBagInterface
     */
    private ParameterBagInterface $parameterBag;
    /**
     * @var ?UploaderHelper
     */
    private ?UploaderHelper $vichHelper;

    /**
     * @inheritDoc
     */
    public function __construct(ParameterBagInterface $parameterBag, ?UploaderHelper $vichHelper = null)
    {
        $this->reader       = new AnnotationReader();
        $this->parameterBag = $parameterBag;
        $this->vichHelper   = $vichHelper;
    }


    public function doExport(
        string $tmpDir,
        $object,
        ?ReflectionProperty $property = null
    ) {
        if ($this->vichHelper === null) {
            return null;
        }

        /** @var UploadableField $annotation */
        $annotation = $this->reader->getPropertyAnnotation($property, UploadableField::class);
        $imgPath    = $this->vichHelper->asset($object, $property->getName());

        if ($imgPath === null || $annotation === null) {
            return null;
        }

       try{
           $publicDir = $this->parameterBag->get('kernel.project_dir') . '/public';
           $getter    = 'get' . ucfirst($annotation->getFileNameProperty());

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
