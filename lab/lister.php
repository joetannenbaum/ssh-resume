<?php

use ChewieLab\Lister;

require __DIR__ . '/../vendor/autoload.php';

$path = $argv[1] ?? getcwd();

$path = realpath($path);

if (!is_dir($path)) {
    echo "Not a directory: $path\n";
    exit(1);
}

$value = (new Lister($path))->watch();
