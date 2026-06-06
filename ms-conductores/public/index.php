<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/Config/db.php';

use Slim\Factory\AppFactory;
use App\Middleware\CorsMiddleware;

$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->add(new CorsMiddleware());
$app->addErrorMiddleware(true, true, true);
$app ->get('/', function($request, $response){
    $data = [
        'message' => 'ms-conductores funciona correctamente'
    ];
    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});
(require __DIR__ . '/../app/Routes/conductores.php')($app);
$app -> run();
