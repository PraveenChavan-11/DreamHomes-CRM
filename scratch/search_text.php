<?php
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('.'));
foreach ($it as $f) {
    if (strpos($f->getPathname(), '.git') !== false || strpos($f->getPathname(), 'vendor') !== false) continue;
    if ($f->isFile()) {
        $c = @file_get_contents($f->getPathname());
        if ($c && (stripos($c, 'Stay Ahead in the Pune') !== false || stripos($c, 'zero spam') !== false)) {
            echo "FOUND in " . $f->getPathname() . "\n";
        }
    }
}

