<?php

namespace App\CsvProcessor;

use App\CsvProcessor\Matchers\BaseMatcher;
use App\CsvProcessor\Matchers\MatcherInterface;

class Processor
{
    private array $matchers;
    private array $matches = [];
    private int $thresholdScore;
    private string $idColumn;
    private array $idMap;
    private array $headers;
    private string $file;

    public function __invoke($filePath, string $idColumn, int $thresholdScore, BaseMatcher ...$matchers): array
    {
        try {
            $this->file = $filePath;
            $this->thresholdScore = $thresholdScore;
            $this->idColumn = $idColumn;
            $this->validateFile();
            foreach ($matchers as $matcher) {
                $this->registerMatchers($matcher);
            }
            $this->process();

            return $this->generateResult();
        } catch (\Exception $e) {
            echo $e->getMessage();
        }

        return [];
    }

    private function process(): void
    {
        $id = 0;
        foreach ($this->getCsvRow() as $record) {
            if ($id == 0) {
                $id++;
                continue;
            }
            if (!empty($record)) {
                $this->idMap[$id] = $record[$this->idColumn];
                $this->processMatchers($id, $record);
            }
            $id++;
        }

        $this->parseMatches();
    }

    private function processMatchers(int $id, array $record): void
    {
        foreach ($this->matchers as $column => $matcher) {
            $value = $record[$column];
            if (trim($value)) {
                $matcher->process($id, $value);
            }
        }
    }

    private function parseMatches()
    {
        foreach ($this->matchers as $matcher) {
            $score = $matcher->getScore();
            foreach ($matcher->getMatches() as $match) {
                $this->matches[$match] += $score;
            }
        }
    }

    private function generateResult()
    {

        arsort($this->matches);
        $pairs = [];
        foreach ($this->matches as $ids => $score) {
            if ($score < $this->thresholdScore) {
                break;
            }

            list($id1, $id2) = explode('_', $ids);
            $pairs[] = [
                'objects' => [$this->idMap[$id1], $this->idMap[$id2]],
                'score' => $score
            ];
        }

        return $pairs;
    }

    private function getCsvRow()
    {
        $handle = fopen($this->file, "r");

        while (!feof($handle)) {
            yield fgetcsv($handle);
        }

        fclose($handle);
    }

    private function registerMatchers(BaseMatcher $matcher)
    {
        $headerId = $this->validateColumn($matcher->getColumnName());
        $this->matchers[$headerId] = $matcher;
    }

    private function validateFile()
    {

        $f = fopen($this->file, 'r');
        $firstLine = fgets($f);
        fclose($f);

        $this->headers = str_getcsv(trim($firstLine), ','); //parse to array

        $this->idColumn = $this->validateColumn($this->idColumn);
    }

    private function validateColumn(string $column): int
    {
        if (!in_array($column, $this->headers)) {
            throw new \Exception('$column does not exist in file');
        }

        return array_search($column, $this->headers);
    }
}