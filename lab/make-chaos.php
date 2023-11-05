<?php

// Generate a random string
function randomString($length = 10)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';

    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }

    return trim($randomString);
}

function randomExtension()
{
    $extensions = ['txt', 'json', 'php', 'js', 'ts', 'csv'];

    return $extensions[rand(0, count($extensions) - 1)];
}

function randomFilename()
{
    return randomString() . '.' . randomExtension();
}

foreach (glob(__DIR__ . '/chaos/*') as $file) {
    if (is_dir($file)) {
        rmdir($file);
    } else {
        unlink($file);
    }
}

if (($argv[1] ?? null) === 'clean') {
    exit;
}

$addFile = function () {
    $filename = randomFilename();
    file_put_contents(__DIR__ . '/chaos/' . $filename, randomString());

    echo "Added $filename\n";
};

$makeDir = function () {
    $dir = randomString();

    mkdir(__DIR__ . '/chaos/' . $dir);

    echo "Made directory $dir\n";
};

$removeFile = function () {
    $files = glob(__DIR__ . '/chaos/*');

    if (count($files) === 0) {
        return;
    }

    $file = $files[rand(0, count($files) - 1)];

    if (is_dir($file)) {
        rmdir($file);
    } else {
        unlink($file);
    }

    echo 'Removed ' . basename($file) . "\n";
};

$chmod = function () {
    $files = glob(__DIR__ . '/chaos/*');

    if (count($files) === 0) {
        return;
    }

    $file = $files[rand(0, count($files) - 1)];

    $modes = [0777, 0755, 0700];

    chmod($file, $modes[rand(0, count($modes) - 1)]);

    echo 'Chmod ' . basename($file) . "\n";
};

$touch = function () {
    $files = glob(__DIR__ . '/chaos/*');

    if (count($files) === 0) {
        return;
    }

    $file = $files[rand(0, count($files) - 1)];

    if (is_dir($file)) {
        return;
    }

    touch($file);

    echo 'Touched ' . basename($file) . "\n";
};

$addToFile = function () {
    $files = glob(__DIR__ . '/chaos/*');

    if (count($files) === 0) {
        return;
    }

    $file = $files[rand(0, count($files) - 1)];

    if (is_dir($file)) {
        return;
    }

    file_put_contents($file, randomString(), FILE_APPEND);

    echo 'Wrote to ' . basename($file) . "\n";
};

$actions = [
    $addFile,
    $makeDir,
    $chmod,
    $touch,
    $addToFile,
];

$actions = array_merge($actions);
$actions = array_merge($actions);
$actions = array_merge($actions);

$actions[] = $removeFile;

while (true) {
    $actions[rand(0, count($actions) - 1)]();

    usleep(rand(500_000, 500_000 * 2));
}
