<?php


namespace WebEtDesign\UserBundle\Services\Exporter;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use WebEtDesign\UserBundle\Attribute\Exportable;
use WebEtDesign\UserBundle\Exporter\ExporterFileInterface;
use WebEtDesign\UserBundle\Utils\LoopGuard;
use ZipArchive;

class Exporter implements ExporterInterface
{
    private EntityManagerInterface $em;

    private LoopGuard $loopGuard;
    private ContainerInterface $container;

    private string $tmpDir;

    private RouterInterface $router;

    /**
     * @var array<ExporterFileInterface>
     */
    private array $exporter = [];

    /**
     * @inheritDoc
     */
    public function __construct(
        EntityManagerInterface $em,
        ContainerInterface $container,
        RouterInterface $router
    ) {
        $this->em        = $em;
        $this->loopGuard = new LoopGuard();
        $this->container = $container;
        $this->router    = $router;
    }

    public function export($object)
    {
        $data = $this->doExport($object);

        $zip = $this->doArchive();

        if ($zip !== null) {
            $zipUrl           = $this->router->generate('rgpd_zip_download', ['filename' => $zip],
                UrlGeneratorInterface::ABSOLUTE_URL);
            $data['_archive'] = $zipUrl;
        }

        return json_encode($data);
    }


    private function doExport($object)
    {
        $output = [];

        $metadata  = $this->em->getClassMetadata(get_class($object));
        $className = $metadata->rootEntityName;
        $shortName = $metadata->getReflectionClass()->getShortName();

        if (($classAnnotation = $this->getAnnotation($className))) {
            if ($this->loopGuard->contains($className, $object->getId())) {
                return (!empty($classAnnotation->getName()) ? $classAnnotation->getName() : $shortName) . ' ' . $object->getId();
            }
            $this->loopGuard->add($className, $object->getId());

            $reflectionClass = $metadata->getReflectionClass();
            foreach ($reflectionClass->getProperties() as $property) {
                $annotation = $this->getPropertyAnnotation($property);
                if ($annotation !== null) {

                    $name = !empty($annotation->getName()) ? $annotation->getName() : $property->getName();

                    if ($metadata->hasField($property->getName())) {
                        $getter = 'get' . ucfirst($property->getName());

                        $output[$name] = $object->$getter();
                    }

                    if ($metadata->hasAssociation($property->getName())) {
                        $mapping = $metadata->getAssociationMapping($property->getName());
                        switch ($mapping['type']) {
                            case ClassMetadataInfo::MANY_TO_MANY:
                            case ClassMetadataInfo::ONE_TO_MANY:
                                $getter = 'get' . ucfirst($property->getName());

                                $output[$name] = [];
                                foreach ($object->$getter() as $item) {
                                    $output[$name][] = $item ? $this->doExport($item) : null;
                                }
                                break;
                            case ClassMetadataInfo::MANY_TO_ONE:
                            case ClassMetadataInfo::ONE_TO_ONE:
                                $getter = 'get' . ucfirst($property->getName());
                                if ($annotation->getType() === Exportable::TYPE_SONATA_MEDIA) {
                                    $exporter = $this->getExporter(Exportable::TYPE_SONATA_MEDIA);
                                    if ($exporter) {
                                        $output[$name] = $object->$getter() ? $this->doExport($object->$getter()) : null;
                                    } else {
                                        $output[$name] = null;
                                    }
                                } else {
                                    $output[$name] = $object->$getter() ? $this->doExport($object->$getter()) : null;
                                }

                                break;
                        }
                    }

                    if ($annotation->getType() === Exportable::TYPE_VICH_UPLOADER) {
                        $exporter = $this->getExporter(Exportable::TYPE_VICH_UPLOADER);
                        if ($exporter) {
                            $output[$name] = $exporter->doExport($this->getTmpDir(), $object,
                                $property);
                        } else {
                            $output[$name] = null;
                        }
                    }
                }
            }
        }


        return $output;
    }


    private function getAnnotation(string $className): ?Exportable
    {
        $reflectionClass = new ReflectionClass($className);

        $attributes = $reflectionClass->getAttributes(Exportable::class);

        if (empty($attributes)) {
            return null;
        }

        return $attributes[0]->newInstance();
    }

    private function getPropertyAnnotation(ReflectionProperty $property): ?Exportable
    {
        $attributes = $property->getAttributes(Exportable::class);

        if (empty($attributes)) {
            return null;
        }

        return $attributes[0]->newInstance();
    }

    private function getTmpDir()
    {
        if (!empty($this->tmpDir)) {
            return $this->tmpDir;
        }

        $this->tmpDir = sys_get_temp_dir() . '/' . uniqid();

        mkdir($this->tmpDir);

        return $this->tmpDir;
    }

    private function doArchive()
    {
        $uid        = uniqid();
        $archiveDir = $this->container->getParameter('kernel.project_dir') . '/' .
            $this->container->getParameter('wd_user.export.zip_private_path');

        if (!file_exists($archiveDir)) {
            $fs = new Filesystem();
            $fs->mkdir($archiveDir);
        }

        $zipName = $uid . '.zip';
        $zipPath = $archiveDir . '/' . $zipName;

        $finderFile = new Finder();
        $finderFile->depth('==0');
        $finderFile->in($this->getTmpDir());

        if ($finderFile->count() === 0) {
            return null;
        }

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE) === true) {

            foreach ($finderFile as $file) {
                $zip->addFile($file->getPathname(), $uid . '/' . $file->getFilename());
            }
            $zip->close();
        }

        return $zipName;
    }

    public function addExporter(ExporterFileInterface $exporterFile, $key): void
    {
        $this->exporter[$key] = $exporterFile;
    }

    public function getExporter($key): ?ExporterFileInterface
    {
        return $this->exporter[$key] ?? null;
    }

}
