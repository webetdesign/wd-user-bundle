<?php

declare(strict_types=1);

/**
 * Regression check: every class/interface/trait of src/ must load (missing imports and broken
 * inheritance only fail at autoload time, e.g. when a merge drops a `use` statement).
 * Dev-only fixtures and code-generation templates (*.tpl.php) are skipped.
 *
 * Usage: php tests/load-all-classes.php [path/to/vendor/autoload.php]
 */

require $argv[1] ?? __DIR__ . '/../vendor/autoload.php';

$srcDir = realpath(__DIR__ . '/../src');
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($srcDir, FilesystemIterator::SKIP_DOTS));
$checked = 0;
$problems = 0;
foreach ($it as $file) {
    if ('php' !== $file->getExtension() || str_contains($file->getPathname(), '/DataFixtures/') || str_ends_with($file->getPathname(), '.tpl.php')) {
        continue;
    }
    $class = 'WebEtDesign\\UserBundle\\' . str_replace('/', '\\', substr($file->getPathname(), strlen($srcDir) + 1, -4));
    ++$checked;
    try {
        if (!class_exists($class) && !interface_exists($class) && !trait_exists($class) && !enum_exists($class)) {
            echo "FAIL missing $class\n";
            ++$problems;
        }
    } catch (Throwable $e) {
        echo 'FAIL ', $class, ': ', substr(str_replace("\n", ' ', $e->getMessage()), 0, 160), "\n";
        ++$problems;
    }
}
echo "checked=$checked problems=$problems\n";
exit($problems > 0 ? 1 : 0);
