<?php
use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// Create Container using PHP-DI
$container = new Container();

$container->set('view', function (ContainerInterface $container) {
    return Twig::create(__DIR__ . '/../app/View',
        ['cache' => false ]);
});
// Set container to create App with on AppFactory
AppFactory::setContainer($container);
// Instantiate App
$app = AppFactory::create();

$container = $app->getContainer();


$container->set(\App\Controller\MainController::class, function (ContainerInterface $c) {
    $view = $c->get('view');
    return new \App\Controller\MainController($view);
});
// Add error middleware
$app->addErrorMiddleware(true, true, true);
$app->get('/', \App\Controller\MainController::class . ':main');
$app->post('/upload', \App\Controller\MainController::class . ':upload');
$app->get('/result/{id}', \App\Controller\MainController::class . ':resultPage');
$app->post('/result', \App\Controller\MainController::class . ':resultData');

$app->run();