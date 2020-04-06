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
    private array $pairs;

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

    private function getMatches(): \Generator
    {
        foreach ($this->matches as $ids => $value) {
            yield [explode('_', $ids), $value];
        }
    }

    private function generateResult()
    {
        $avgScore = array_sum($this->matches) / count($this->matches);

        arsort($this->matches);
        $totalScore = 0;
        $totalCount = 0;
        $exclude = [];
        foreach ($this->getMatches() as $match) {
            list($objects, $score) = $match;
            if (array_intersect($exclude, $objects)) {
                continue;
            }

            $totalScore += $score;
            $totalCount++;
            $this->pairs[] = [
                'objects' => [$this->idMap[$objects[0]], $this->idMap[$objects[1]]],
                'score' => $score
            ];
            unset($this->idMap[$objects[0]], $this->idMap[$objects[1]]);
            $exclude[] = $objects[0];
            $exclude[] = $objects[1];
        }

        /**
         * Zero matches
         */
        $zeroMatchPair = [];
        $zeroMatchCounter = 0;
        foreach ($this->idMap as $name) {
            $zeroMatchCounter++;
            $totalCount++;
            $zeroMatchPair[] = $name;
            if($zeroMatchCounter == 2) {
                $this->pairs[] = [
                    'objects' => $zeroMatchPair,
                    'score' => 0
                ];
                $zeroMatchPair = [];
                $zeroMatchCounter = 0;
            }
        }

        return ['pairs'=> $this->pairs, 'avgScore' => round($totalScore / $totalCount)];
    }

    private function getPairs(): \Generator
    {
        foreach ($this->pairs as $pair) {
            yield $pair;
        }
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

        $this->headers = str_getcsv(trim($firstLine), ',');

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