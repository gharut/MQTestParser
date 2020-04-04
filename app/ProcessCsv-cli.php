<?php
set_time_limit(300);
use \App\ProcessCsv;
require __DIR__ . '/../vendor/autoload.php';

$files = array_slice($argv, 1);
$processor = new ProcessCsv;
foreach ($files as $file) {
    if(is_file($file)) {
        $processor($file);
    }
}