<?php
use App\Controllers\VehiculoController;
use App\Middleware\AuthMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $app->group('/api/vehiculos', function (RouteCollectorProxy $group): void {
        $group->get('', [VehiculoController::class, 'index']);
        $group->post('', [VehiculoController::class, 'store']);
        $group->get('/{id}', [VehiculoController::class, 'show']);
        $group->put('/{id}', [VehiculoController::class, 'update']);
        $group->patch('/{id}/estado', [VehiculoController::class, 'cambiarEstado']);
    })->add(new AuthMiddleware());
};
