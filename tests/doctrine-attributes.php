<?php

// Standalone integration: php tests/doctrine-attributes.php /path/to/vendor/autoload.php [case]
// Uses real Doctrine attribute metadata, synthetic objects, no SQL or application kernel.
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\DependencyInjection\Container;

use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Router;
use WebEtDesign\UserBundle\Services\Exporter\Exporter;
use WebEtDesign\UserBundle\Tests\Fixtures\RgpdRecord;
use WebEtDesign\UserBundle\Tests\Fixtures\UnselectedRecord;

$autoload = $argv[1] ?? dirname(__DIR__) . '/vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "Provide an installed vendor/autoload.php as the first argument.\n");
    exit(2);
}
$loader = require $autoload;
// The candidate bundle must win over the installed bundle's namespace mapping.
$loader->addPsr4('WebEtDesign\\UserBundle\\', dirname(__DIR__) . '/src/', true);
require __DIR__ . '/Fixtures/RgpdRecord.php';
$config = ORMSetup::createAttributeMetadataConfiguration([__DIR__ . '/Fixtures'], true, null, new ArrayAdapter());
$connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
$em = method_exists(EntityManager::class, 'create')
    ? EntityManager::create($connection, $config)
    : new EntityManager($connection, $config);
$container = new Container();
$router = new Router(new class extends Symfony\Component\Config\Loader\Loader {
    public function load(mixed $resource, ?string $type = null): RouteCollection {
        $routes = new RouteCollection();
        $routes->add('rgpd_zip_download', new Symfony\Component\Routing\Route('/archives/{filename}'));
        return $routes;
    }
    public function supports(mixed $resource, ?string $type = null): bool { return true; }
}, 'synthetic');
$router->setContext(new RequestContext('', 'GET', 'example.test', 'https'));

function same($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true));
    }
}

function exportData(Exporter $exporter, object $record): array|string
{
    // This slice deliberately avoids archive creation; archive tested separately.
    return (new ReflectionMethod(Exporter::class, 'doExport'))->invoke($exporter, $record);
}

$cases = [
    'association_action_matrix' => function () use ($em): void {
        $method = new ReflectionMethod(WebEtDesign\UserBundle\Services\Anonymizer\Anonymizer::class, 'doAssociationAnonimize');
        $attributeClass = WebEtDesign\UserBundle\Attribute\Anonymizer::class;
        foreach (['tags', 'children', 'parent', 'partner'] as $field) {
            foreach ([$attributeClass::ACTION_SET_NULL, $attributeClass::ACTION_CASCADE] as $action) {
                $root = new RgpdRecord(100);
                $target = new RgpdRecord(101);
                $metadata = $em->getClassMetadata(RgpdRecord::class);
                $collection = $metadata->isCollectionValuedAssociation($field);
                $getter = 'get' . ucfirst($field);
                $setter = 'set' . ucfirst($field);
                if ($collection) { $root->$getter()->add($target); } else { $root->$setter($target); }
                $service = new WebEtDesign\UserBundle\Services\Anonymizer\Anonymizer($em, new Symfony\Component\EventDispatcher\EventDispatcher());
                $attribute = new $attributeClass(action: $action);
                $property = new ReflectionProperty(RgpdRecord::class, $field);
                $method->invoke($service, $root, $property, $attribute, $metadata);
                if ($action === $attributeClass::ACTION_SET_NULL) {
                    same($collection ? 0 : null, $collection ? $root->$getter()->count() : $root->$getter(), "$field SET_NULL must remove the association");
                    same('synthetic', $target->getName(), "$field SET_NULL must not anonymize its target");
                } else {
                    same('anonymous', $target->getName(), "$field CASCADE must anonymize its target");
                    same($target, $collection ? $root->$getter()->first() : $root->$getter(), "$field CASCADE must retain its association");
                }
                // A second pass also exercises empty collections, nulls and the cascade loop guard.
                $method->invoke($service, $root, $property, $attribute, $metadata);
                $em->clear();
            }
        }
    },
    'archive_and_typed_export' => function () use ($em, $container, $router): void {
        $scratch = getenv('TMPDIR');
        if (!$scratch || !is_dir($scratch)) { throw new RuntimeException('Set TMPDIR to an isolated scratch directory'); }
        $dir = rtrim($scratch, '/') . '/wd-user-archive-' . bin2hex(random_bytes(6));
        mkdir($dir . '/files', 0700, true);
        $container->setParameter('kernel.project_dir', $dir);
        $container->setParameter('wd_user.export.zip_private_path', 'archives');
        $exporter = new Exporter($em, $container, $router);
        (new ReflectionProperty(Exporter::class, 'tmpDir'))->setValue($exporter, $dir . '/files');
        $fileExporter = new class implements WebEtDesign\UserBundle\Exporter\ExporterFileInterface {
            public function doExport(string $tmpDir, $object, ?ReflectionProperty $property = null) {
                file_put_contents($tmpDir . '/synthetic.txt', 'synthetic attachment');
                return 'synthetic.txt';
            }
        };
        $exporter->addExporter($fileExporter, WebEtDesign\UserBundle\Attribute\Exportable::TYPE_VICH_UPLOADER);
        $exporter->addExporter($fileExporter, WebEtDesign\UserBundle\Attribute\Exportable::TYPE_SONATA_MEDIA);
        $root = new RgpdRecord(20);
        $root->setMedia(new RgpdRecord(21));
        $data = json_decode($exporter->export($root), true, 512, JSON_THROW_ON_ERROR);
        same('synthetic.txt', $data['attachment'], 'Vich property type must invoke the registered exporter');
        same('synthetic', $data['media']['label'], 'Sonata property type with exporter must retain recursive export');
        same(true, str_starts_with($data['_archive'], 'https://example.test/archives/'), 'Archive must retain its absolute download URL');
        $zipName = basename(parse_url($data['_archive'], PHP_URL_PATH));
        $zip = new ZipArchive();
        same(true, $zip->open($dir . '/archives/' . $zipName), 'Generated archive must be readable');
        same(1, $zip->numFiles, 'Archive must contain the synthetic attachment');
        same(pathinfo($zipName, PATHINFO_FILENAME) . '/synthetic.txt', $zip->getNameIndex(0), 'Archive must retain UID-prefixed entries');
        same('synthetic attachment', $zip->getFromIndex(0), 'Archive must retain attachment content');
        $zip->close();
        echo "ARTIFACT $dir/archives/$zipName\n";

        $empty = new Exporter($em, $container, $router);
        mkdir($dir . '/empty', 0700);
        (new ReflectionProperty(Exporter::class, 'tmpDir'))->setValue($empty, $dir . '/empty');
        $emptyData = json_decode($empty->export(new RgpdRecord(22)), true, 512, JSON_THROW_ON_ERROR);
        same(false, array_key_exists('_archive', $emptyData), 'No files must mean no archive URL');
    },
    'association_behaviors' => function () use ($em, $container, $router): void {
        $root = new RgpdRecord(10);
        $tag = new RgpdRecord(11);
        $child = new RgpdRecord(12);
        $parent = new RgpdRecord(13);
        $partner = new RgpdRecord(14);
        $root->getTags()->add($tag);
        $root->getChildren()->add($child);
        $root->setParent($parent);
        $root->setPartner($partner);
        $partner->setPartner($root); // One-to-one cycle.
        $child->setParent($root); // One-to-many/many-to-one cycle.
        $data = exportData(new Exporter($em, $container, $router), $root);
        same('synthetic', $data['tags'][0]['label'], 'Many-to-many export must recurse');
        same('record 10', $data['children'][0]['parent'], 'Collection cycle must use class export name and ID');
        same('synthetic', $data['parent']['label'], 'Many-to-one export must recurse');
        same('record 10', $data['partner']['partner'], 'Single association cycle must stop');

        $service = new WebEtDesign\UserBundle\Services\Anonymizer\Anonymizer($em, new Symfony\Component\EventDispatcher\EventDispatcher());
        $service->anonimize($root); // persist only schedules in UnitOfWork; never flush.
        same('anonymous', $root->getName(), 'Marked scalar must be anonymized');
        same('not exported', $root->getSecret(), 'Unmarked scalar must be preserved');
        same(0, $root->getTags()->count(), 'SET_NULL collection must remove its elements');
        same(null, $root->getParent(), 'SET_NULL single relation must clear its value');
        same('synthetic', $tag->getName(), 'SET_NULL must not cascade anonymization');
        same('synthetic', $parent->getName(), 'SET_NULL must not anonymize the previous target');
        same('anonymous', $child->getName(), 'CASCADE collection must anonymize children');
        same('anonymous', $partner->getName(), 'CASCADE single relation must anonymize its target');
        same($root, $partner->getPartner(), 'CASCADE must retain the relation and stop cycles');
        same(true, $root->getAnonymizedAt() instanceof DateTime, 'RGPD timestamp must remain set');
        same(true, $child->getAnonymizedAt() instanceof DateTime, 'Cascade timestamp must remain set');
        same(true, $em->contains($root), 'Anonymized root must still be scheduled for persistence');
        same(true, $em->contains($partner), 'Anonymized cascade target must still be scheduled for persistence');
    },
    'orm3_exporter' => function (): void {
        $source = file_get_contents(dirname(__DIR__) . '/src/Services/Exporter/Exporter.php');
        same(false, str_contains($source, 'ClassMetadataInfo'), 'Exporter must not refer to the class removed in ORM 3');
        same(false, str_contains($source, "\$mapping['type']"), 'Exporter must use metadata cardinality instead of mapping representation');
    },
    'orm3_anonymizer' => function (): void {
        $source = file_get_contents(dirname(__DIR__) . '/src/Services/Anonymizer/Anonymizer.php');
        same(false, str_contains($source, 'ClassMetadataInfo'), 'Anonymizer must not refer to the class removed in ORM 3');
        same(false, str_contains($source, "\$mapping['type']"), 'Anonymizer must use metadata cardinality instead of mapping representation');
    },
    'export_attributes' => function () use ($em, $container, $router): void {
        $exporter = new Exporter($em, $container, $router);
        $data = exportData($exporter, new RgpdRecord(1));
        same('synthetic', $data['label'] ?? null, 'Class/property attributes and renamed field must be read');
        same(false, array_key_exists('secret', $data), 'Unmarked fields must not be exported');
        same(['label', 'tags', 'children', 'parent', 'partner', 'media', 'attachment'], array_keys($data), 'Only selected properties must be exported');
        same([], $data['tags'], 'Empty collection must remain an array');
        same(null, $data['parent'], 'Null single association must remain null');
        same(null, $data['media'], 'Missing Sonata exporter must retain null');
        same(null, $data['attachment'], 'Missing Vich exporter must retain null');
        same([], exportData($exporter, new UnselectedRecord()), 'Class without Exportable must be excluded');
    },
];

$failed = 0;
$selected = $argv[2] ?? null;
if ($selected !== null && !isset($cases[$selected])) {
    fwrite(STDERR, "Unknown test case: $selected\n");
    exit(2);
}
foreach ($cases as $name => $case) {
    if ($selected !== null && $name !== $selected) { continue; }
    try {
        $case();
        echo "PASS $name\n";
    } catch (Throwable $error) {
        ++$failed;
        fwrite(STDERR, "FAIL $name: " . $error->getMessage() . "\n" . $error->getTraceAsString() . "\n");
    }
}
same(false, $connection->isConnected(), 'Tests must not open a database connection');
echo 'Doctrine ORM ' . Composer\InstalledVersions::getPrettyVersion('doctrine/orm') . "; failures=$failed; database_connected=no\n";
exit($failed === 0 ? 0 : 1);
