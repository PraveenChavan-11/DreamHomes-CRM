<?php
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('app'));
$errors = 0;
foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $output = [];
        $returnVar = 0;
        exec('php -l "' . $file->getPathname() . '" 2>&1', $output, $returnVar);
        if ($returnVar !== 0) {
            echo "SYNTAX ERROR in " . $file->getPathname() . ":\n";
            echo implode("\n", $output) . "\n\n";
            $errors++;
        }
    }
}
if ($errors === 0) {
    echo "SUCCESS: 100% of app PHP files passed syntax check with zero errors.\n";
} else {
    echo "FAILED with $errors errors.\n";
}

