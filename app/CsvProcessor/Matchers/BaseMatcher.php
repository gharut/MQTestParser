<?php

namespace App\CsvProcessor\Matchers;

abstract class BaseMatcher implements MatcherInterface
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

    protected function createPair(int $id1, int $id2): void
    {
        $id = $id1 < $id2 ? $id1 . '_' . $id2 : $id2 . '_' . $id1;
        $this->matches[] = $id;
    }

    public function getColumnName()
    {
        return $this->columnName;
    }

    public function process(int $id, string $value)
    {
        $this->processValue($id, $value);

        if (!$this->map[$value]) {
            $this->map[$value] = [];
        }
        $this->map[$value][] = $id;
    }

    protected function processValue(int $id, string $value)
    {
        if ($this->map[$value]) {
            foreach ($this->map[$value] as $pair) {
                $this->createPair($id, $pair);
            }
        }
    }

    public function getMatches(): array
    {
        return $this->matches;
    }

    public function getScore(): int
    {
        return $this->score;
    }
}