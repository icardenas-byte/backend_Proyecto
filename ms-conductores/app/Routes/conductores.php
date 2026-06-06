<?php
use App\Controllers\ConductorController;
use App\Middleware\AuthMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $app->group('/api/conductores', function (RouteCollectorProxy $group): void {
        $group->get('', [ConductorController::class, 'index']);
        $group->post('', [ConductorController::class, 'store']);
        $group->get('/{id}', [ConductorController::class, 'show']);
        $group->put('/{id}', [ConductorController::class, 'update']);
        $group->patch('/{id}/estado', [ConductorController::class, 'cambiarEstado']);
    })->add(new AuthMiddleware());
};
