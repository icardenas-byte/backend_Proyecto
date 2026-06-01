<?php
use Slim\Routing\RouteCollectorProxy;
use App\Controllers\ConductorController;
use App\Middleware\AuthMiddleware;

return function ($app) {
    
    // ============================================
    // RUTA GET TEMPORAL - PRUEBA DE CONEXIÓN
    // QUITAR DESPUÉS DE VERIFICAR
    // ============================================
    $app->get('/test', function ($request, $response) {
        try {
            // Probar conexión a la base de datos
            $conductores = \App\Models\Conductor::all();
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Conexión a base de datos exitosa',
                'total_conductores' => count($conductores),
                'conductores' => $conductores
            ]));
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Error de conexión',
                'error' => $e->getMessage()
            ]));
        }
        return $response->withHeader('Content-Type', 'application/json');
    });
    
    // ============================================
    // RUTAS NORMALES (protegidas con AuthMiddleware)
    // ============================================
    $app->group('/api/conductores', function (RouteCollectorProxy $group) {
        $group->get('', [ConductorController::class, 'index']);
        $group->post('', [ConductorController::class, 'store']);
        $group->get('/{id}', [ConductorController::class, 'show']);
        $group->put('/{id}', [ConductorController::class, 'update']);
        $group->patch('/{id}/estado', [ConductorController::class, 'cambiarEstado']);
        $group->get('/documento/{documento}', [ConductorController::class, 'buscarDocumento']);
        $group->get('/licencia/{licencia}', [ConductorController::class, 'buscarLicencia']);
        $group->get('/estado/{estado}', [ConductorController::class, 'buscarEstado']);
    })->add(new AuthMiddleware());
};