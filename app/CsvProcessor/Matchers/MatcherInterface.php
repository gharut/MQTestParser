<?php

namespace App\CsvProcessor\Matchers;

interface MatcherInterface
{
    function __construct(string $columnName, int $score);
    function getColumnName();
    function process(int $id, string $value);
}