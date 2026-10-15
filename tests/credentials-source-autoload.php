<?php
// CLI auto_prepend_file for existing suites using an optimized app autoloader.
$loader = require '/app/vendor/autoload.php';
$source = dirname(__DIR__) . '/src/';
$map = [];
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source)) as $file) {
    if ($file->getExtension() === 'php') {
        $map['WebEtDesign\\UserBundle\\' . str_replace('/', '\\', substr($file->getPathname(), strlen($source), -4))] = $file->getPathname();
    }
}
$loader->addClassMap($map);
$loader->setPsr4('WebEtDesign\\UserBundle\\', $source);
if ((new ReflectionClass(WebEtDesign\UserBundle\Entity\WDUser::class))->getFileName() !== $source . 'Entity/WDUser.php') { throw new RuntimeException('Wrong source'); }
