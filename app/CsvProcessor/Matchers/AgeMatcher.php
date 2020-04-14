<?php

namespace App\CsvProcessor\Matchers;

class AgeMatcher extends BaseMatcher
{
    protected function processValue(int $id, string $value, array $row)
    {
        foreach ($this->map as $age => $items) {
            $difference = $age - intval($value);
            if ($difference >= -5 && $difference <= 5) {
                foreach($items as $pair) {
                    $this->createPair($id, $pair, $row);
                }
            }
        }
    }
}