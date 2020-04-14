<?php

namespace App;

use App\CsvProcessor\Conditions\LocationCondition;
use App\CsvProcessor\Processor;
use App\CsvProcessor\Matchers\SimpleMatcher;
use App\CsvProcessor\Matchers\AgeMatcher;

class ProcessCsv
{
    private string $file;

    public function __invoke(string $file)
    {
        try {
            if (!file_exists($file)) {
                throw new \Exception('file not uploaded');
            }
            $this->file = $file;
            $this->writeResult(['status' => 'processing', 'file' => basename($file)]);
            $result = ['status' => true];
            $processor = new Processor($file, 'Name');
            $processor->addMatchers(
                new SimpleMatcher('Division', 30),
                new SimpleMatcher('Timezone', 40),
                new AgeMatcher('Age', 30),
            );

            $processor->addConditions(
                new LocationCondition()
            );
            $data = $processor->process();
        } catch (\Throwable $e) {
            $result['status'] = false;
            $data =  $e->getMessage().$e->getFile().$e->getLine();
        }
        $result['data'] = $data;
        $result['file'] = basename($file);
        $this->writeResult($result);
        return $result['status'];
    }

    private function writeResult(array $data)
    {
        file_put_contents(self::getResultFile($this->file), json_encode($data));
    }

    public static function getResultFile(string $file)
    {
        if (!file_exists($file)) {
            throw new \Exception('File does not exist');
        }
        return Config::RESULT_FILES_DIRECTORY.'/'.md5(filesize($file) . basename($file));
    }
}