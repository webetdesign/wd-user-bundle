<?php
// Source guard: the bundle must not invoke its PHP-deprecated compatibility API.
declare(strict_types=1);
$bad = 0;
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__) . '/src')) as $file) {
    if ($file->getExtension() !== 'php') { continue; }
    if (preg_match('/->eraseCredentials\s*\(/', file_get_contents($file->getPathname()))) {
        ++$bad;
        echo 'FAIL deprecated internal call: ' . substr($file->getPathname(), strlen(dirname(__DIR__)) + 1) . "\n";
    }
}
echo "deprecated_internal_calls=$bad\n";
exit($bad === 0 ? 0 : 1);
