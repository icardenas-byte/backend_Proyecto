<?php

use Slim\Routing\RouteCollectorProxy;
use App\Controllers\AuthController;
use App\Middleware\AuthMiddleware;

return function ($app) {
    $app->group('/api/auth', function (RouteCollectorProxy $group) {
        $group->post('/login', [AuthController::class, 'login']);
        
        // Ruta GET temporal para prueba - QUITAR DESPUÉS
        $group->get('/login', function ($request, $response) {
            $response->getBody()->write('Usa POST para login, no GET');
            return $response;
        });
        
        $group->post('/logout', [AuthController::class, 'logout'])->add(new AuthMiddleware());
        $group->get('/validate', [AuthController::class, 'validate'])->add(new AuthMiddleware());
    });
};

