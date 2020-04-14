<?php

namespace App\CsvProcessor\Matchers;

use App\CsvProcessor\Processor;

abstract class BaseMatcher
{
    protected array $matches = [];
    protected string $columnName;
    protected int $score;
    protected array $map = [];

    public function __construct(string $columnName, int $score)
    {
        $this->columnName = $columnName;
        $this->score = $score;
    }

    private function getConditionData(array $condition, array $record): array
    {
        $data = [];
        foreach ($condition['columns'] as $columnId => $columnName) {
            $data[$columnName] = $record[$columnId];
        }

        return $data;
    }

    protected function createPair(int $id, int $withId, array $row): void
    {
        $pairAllowed = true;
        foreach (Processor::$conditions as $condition) {
            $conditionData = Processor::getConditionData($condition, $row);
            if (!$condition['processor']->pairAllowed($withId, $conditionData)) {
                $pairAllowed = false;
                break;
            }
        }
        if ($pairAllowed) {
            $match_id = $id < $withId ? $id . '_' . $withId : $withId . '_' . $id;
            $this->matches[] = $match_id;
        }
    }

    public function getColumnName()
    {
        return $this->columnName;
    }

    public function process(int $id, string $value, array $row)
    {
        $this->processValue($id, $value, $row);

        if (!$this->map[$value]) {
            $this->map[$value] = [];
        }
        $this->map[$value][] = $id;
    }

    protected function processValue(int $id, string $value, array $row)
    {
        if ($this->map[$value]) {
            foreach ($this->getValuesMap($value) as $pair) {
                $this->createPair($id, $pair, $row);
            }
        }
    }

    protected function getValuesMap($value): \Generator
    {
        foreach ($this->map[$value] as $pair) {
            yield $pair;
        }
    }

    public function getMatches(): \Generator
    {
        foreach ($this->matches as $key => $value) {
            yield $key => $value;
        }
    }

    public function getScore(): int
    {
        return $this->score;
    }
}