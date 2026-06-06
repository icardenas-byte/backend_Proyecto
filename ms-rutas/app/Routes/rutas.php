<?php
use App\Controllers\ProgramacionController;
use App\Controllers\RutaController;
use App\Middleware\AuthMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $app->group('/api', function (RouteCollectorProxy $api): void {
        $api->group('/rutas', function (RouteCollectorProxy $group): void {
            $group->get('', [RutaController::class, 'index']);
            $group->post('', [RutaController::class, 'store']);
            $group->get('/{id}', [RutaController::class, 'show']);
            $group->put('/{id}', [RutaController::class, 'update']);
        });

        $api->group('/programaciones', function (RouteCollectorProxy $group): void {
            $group->get('', [ProgramacionController::class, 'index']);
            $group->post('', [ProgramacionController::class, 'store']);
            $group->put('/{id}', [ProgramacionController::class, 'update']);
        });
    })->add(new AuthMiddleware());
};
