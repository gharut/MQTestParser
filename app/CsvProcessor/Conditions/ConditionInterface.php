<?php

namespace App\CsvProcessor\Conditions;

interface ConditionInterface {
    function getColumns(): array;
    function registerRow(int $id, array $row): void;
    function pairAllowed($withId, array $row): bool ;
}