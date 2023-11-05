<?php

use ChewieLab\DataTable;

require __DIR__ . '/../vendor/autoload.php';

$data = file_get_contents(__DIR__ . '/datatable/data.json');

$value = (new DataTable(
    ['name' => 'Name', 'email' => 'Email', 'address' => 'Address'],
    json_decode($data, true),
))->prompt();

var_dump($value);
