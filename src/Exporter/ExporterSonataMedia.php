<?php


namespace WebEtDesign\UserBundle\Exporter;


use ReflectionProperty;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ExporterSonataMedia implements ExporterFileInterface
{
    /**
     * @var Pool
     */
    private Pool $pool;
    /**
     * @var ParameterBagInterface
     */
    private ParameterBagInterface $parameterBag;

    /**
     * @inheritDoc
     */
    public function __construct(Pool $pool, ParameterBagInterface $parameterBag) {
        $this->pool = $pool;
        $this->parameterBag = $parameterBag;
    }


    public function doExport(string $tmpDir, $object, ?ReflectionProperty $property = null)
    {
        if (!$object) {
            return null;
        }

        $publicDir = $this->parameterBag->get('kernel.project_dir') . '/public';

        $provider = $this->pool->getProvider($object->getProviderName());
        $path     = $publicDir . $provider->generatePublicUrl($object, 'reference');
        $newPath  = $tmpDir . '/' . $object->getProviderReference();

        copy($path, $newPath);

        return [
            'name' => $object->getName(),
            'file' => $object->getProviderReference(),
        ];
    }
}
