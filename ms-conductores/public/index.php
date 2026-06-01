<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/Config/db.php';

use Slim\Factory\AppFactory;

$app = AppFactory::create();
$app ->get('/', function($request, $response){
    $data = [
        'message' => 'Funciona correctamente'
    ];
    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});
$app -> run();