<?php

namespace App;

class Config
{
    const ADDRESS = 'test.test';
    const UPLOAT_FILES_DIRECTORY = __DIR__ . '/Files/uploaded';
    const RESULT_FILES_DIRECTORY = __DIR__ . '/Files/result';
    const REMOVE_FILE_AFTER_UPLOAD = true;
    const MAX_SYNC_FILE_SIZE = 10; // Max file size in MB for sync answer
    const PROCESS_CSV_CLI = __DIR__ . '/ProcessCsv-cli.php';
}