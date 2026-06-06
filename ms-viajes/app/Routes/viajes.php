<?php
use App\Controllers\ViajeController;
use App\Middleware\AuthMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $app->group('/api/viajes', function (RouteCollectorProxy $group): void {
        $group->get('', [ViajeController::class, 'index']);
        $group->post('/iniciar', [ViajeController::class, 'iniciar']);
        $group->patch('/{id}/estado', [ViajeController::class, 'actualizarEstado']);
        $group->post('/{id}/novedades', [ViajeController::class, 'registrarNovedad']);
        $group->patch('/{id}/finalizar', [ViajeController::class, 'finalizar']);
        $group->get('/{id}/seguimiento', [ViajeController::class, 'seguimiento']);
    })->add(new AuthMiddleware());
};
