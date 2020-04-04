<?php

namespace App\Controller;

use App\Config;
use App\ProcessCsv;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Psr\Container\ContainerInterface;
use Slim\Views\Twig;

class MainController
{
    protected $view;

    public function __construct(Twig $view)
    {
        $this->view = $view;
    }

    public function main(Request $request, Response $response, $args)
    {
        return $this->view->render($response, 'pages/upload.twig');
    }

    public function resultPage(Request $request, Response $response, $args)
    {
        if (!$args['id'] || !file_exists(Config::RESULT_FILES_DIRECTORY . '/' . $args['id'])) {
            $protocol = isset($_SERVER['HTTPS']) and 'https' or 'http';
            $response
                ->withHeader('Location', $protocol . '://' . Config::ADDRESS)
                ->withStatus(301);
        }

        return $this->view->render($response, 'pages/result.twig', ['fileId' => $args['id']]);
    }

    public function resultData(Request $request, Response $response, $args)
    {
        $query = $request->getParsedBody();
        try{
            if (!$query['id'] || !file_exists(Config::RESULT_FILES_DIRECTORY . '/' . $query['id'])) {
                throw new \Exception('Something gone wrong.');
            }
            $data = file_get_contents(Config::RESULT_FILES_DIRECTORY . '/' . $query['id']);
        }catch (\Throwable $e) {
            $data = json_encode(['status'=>false, 'data'=> $e->getMessage(), 'file' => 'Unknown']);
        }



        $response->getBody()->write($data);

        return $response
            ->withHeader('Content-Type', 'application/json');
    }


    public function upload(Request $request, Response $response, $args)
    {
        $result = ['success' => true];
        try {
            $uploaded = $request->getUploadedFiles()['file'] ?? false;
            if (!$uploaded || $uploaded->getError() != UPLOAD_ERR_OK) {
                throw new Exception('File not uploaded');
            }
            $file = Config::UPLOAT_FILES_DIRECTORY . '/' . time() . '_' . preg_replace("/[^a-z0-9\_\-\.]/i", '_',$uploaded->getClientFilename());
            $uploaded->moveTo($file);
            $processor = new ProcessCsv;
            if ($uploaded->getSize() <= Config::MAX_SYNC_FILE_SIZE * 1024 * 1024) {
                $processor($file);
            } else {
                $cmd = 'php ' . Config::PROCESS_CSV_CLI . ' ' . '"'.$file.'"';
                if (substr(php_uname(), 0, 7) == "Windows") {
                    pclose(popen("start /B " . $cmd, "r"));
                } else {
                    exec($cmd . " > /dev/null &");
                }

            }
            $data['id'] = basename($processor::getResultFile($file));

            if(Config::REMOVE_FILE_AFTER_UPLOAD) {
                unlink($file);
            }

        } catch (\Throwable $e) {
            $result['success'] = false;
            $data = $e->getMessage() . '-' . $e->getFile() . ' ' . $e->getLine();
        }

        $result['data'] = $data;

        $response->getBody()->write(json_encode($result));
        return $response
            ->withHeader('Content-Type', 'application/json');
    }

    public function contact(Request $request, Response $response, $args)
    {
        return $this->view->render($response, 'layout.twig');
    }
}