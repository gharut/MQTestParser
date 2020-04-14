<?php

namespace App\CsvProcessor\Conditions;

class LocationCondition implements ConditionInterface{

    private array $filter;
    private array $columns = [
        'Location',
        'Same Location Preference'
    ];

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function registerRow(int $id, array $row): void
    {
        $this->filter[$id] = $row['Same Location Preference'] == 'Yes' ? $row['Location'] : '';
    }

    public function pairAllowed($withId, array $row): bool
    {
        return empty($this->filter[$withId]) || $this->filter[$withId] == $row['Location'];
    }
}