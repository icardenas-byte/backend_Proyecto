<?php
namespace App\Controllers;

use App\Helpers\JsonResponse;
use App\Models\Novedad;
use App\Models\Viaje;
use Carbon\Carbon;
use PDO;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ViajeController
{
    private const ESTADOS = ['Programado', 'En transito', 'Retrasado', 'Finalizado', 'Cancelado'];

    public function index(Request $request, Response $response): Response
    {
        $query = Viaje::query();
        $params = $request->getQueryParams();

        if (!empty($params['estado'])) {
            $query->where('estado', trim($params['estado']));
        }

        if (!empty($params['programacion_viaje_id'])) {
            $query->where('programacion_viaje_id', trim($params['programacion_viaje_id']));
        }

        return JsonResponse::ok($response, $query->orderBy('id', 'desc')->get());
    }

    public function iniciar(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $programacionId = (int) ($body['programacion_viaje_id'] ?? 0);

        if ($programacionId <= 0) {
            return JsonResponse::error($response, 'programacion_viaje_id es obligatorio', 422);
        }

        $programacion = $this->buscarProgramacion($programacionId);
        if (!$programacion) {
            return JsonResponse::error($response, 'La programacion no existe', 404);
        }

        if (($programacion['estado'] ?? '') === 'Cancelado') {
            return JsonResponse::error($response, 'No se puede iniciar un viaje cancelado', 409);
        }

        $viaje = Viaje::firstOrCreate(
            ['programacion_viaje_id' => $programacionId],
            ['estado' => 'Programado', 'observaciones' => $body['observaciones'] ?? null]
        );

        if ($viaje->estado === 'Cancelado') {
            return JsonResponse::error($response, 'No se puede iniciar un viaje cancelado', 409);
        }

        if ($viaje->estado === 'Finalizado') {
            return JsonResponse::error($response, 'El viaje ya esta finalizado', 409);
        }

        $viaje->update([
            'estado' => 'En transito',
            'fecha_inicio' => Carbon::now()->toDateTimeString(),
            'observaciones' => $body['observaciones'] ?? $viaje->observaciones,
        ]);

        return JsonResponse::ok($response, $viaje->fresh(), 201);
    }

    public function actualizarEstado(Request $request, Response $response, array $args): Response
    {
        $viaje = Viaje::find($args['id']);

        if (!$viaje) {
            return JsonResponse::error($response, 'Viaje no encontrado', 404);
        }

        $body = (array) $request->getParsedBody();
        $estado = $this->normalizarEstado(trim($body['estado'] ?? ''));

        if (!in_array($estado, self::ESTADOS, true)) {
            return JsonResponse::error($response, 'Estado invalido', 422);
        }

        if ($viaje->estado === 'Finalizado') {
            return JsonResponse::error($response, 'No se puede modificar un viaje finalizado', 409);
        }

        $viaje->update(['estado' => $estado]);

        if (!empty($body['observaciones'])) {
            Novedad::create([
                'viaje_id' => $viaje->id,
                'tipo' => 'Cambio operativo',
                'descripcion' => 'Cambio de estado a ' . $estado,
                'observaciones' => trim($body['observaciones']),
            ]);
        }

        return JsonResponse::ok($response, $viaje->fresh());
    }

    public function registrarNovedad(Request $request, Response $response, array $args): Response
    {
        $viaje = Viaje::find($args['id']);

        if (!$viaje) {
            return JsonResponse::error($response, 'Viaje no encontrado', 404);
        }

        $body = (array) $request->getParsedBody();
        $tipo = trim($body['tipo'] ?? '');
        $descripcion = trim($body['descripcion'] ?? $body['observaciones'] ?? '');

        if ($tipo === '' || $descripcion === '') {
            return JsonResponse::error($response, 'Tipo y descripcion son obligatorios', 422);
        }

        $novedad = Novedad::create([
            'viaje_id' => $viaje->id,
            'tipo' => $tipo,
            'descripcion' => $descripcion,
            'observaciones' => trim($body['observaciones'] ?? ''),
        ]);

        if ($tipo === 'Retraso' && $viaje->estado === 'En transito') {
            $viaje->update(['estado' => 'Retrasado']);
        }

        return JsonResponse::ok($response, $novedad, 201);
    }

    public function finalizar(Request $request, Response $response, array $args): Response
    {
        $viaje = Viaje::find($args['id']);

        if (!$viaje) {
            return JsonResponse::error($response, 'Viaje no encontrado', 404);
        }

        if (!in_array($viaje->estado, ['En transito', 'Retrasado'], true)) {
            return JsonResponse::error($response, 'No se puede finalizar un viaje no iniciado', 409);
        }

        $body = (array) $request->getParsedBody();
        $viaje->update([
            'estado' => 'Finalizado',
            'fecha_fin' => Carbon::now()->toDateTimeString(),
            'observaciones' => $body['observaciones'] ?? $viaje->observaciones,
        ]);

        return JsonResponse::ok($response, $viaje->fresh());
    }

    public function seguimiento(Request $request, Response $response, array $args): Response
    {
        $viaje = Viaje::find($args['id']);

        if (!$viaje) {
            return JsonResponse::error($response, 'Viaje no encontrado', 404);
        }

        return JsonResponse::ok($response, [
            'viaje' => $viaje,
            'programacion' => $this->buscarProgramacion((int) $viaje->programacion_viaje_id),
        ]);
    }

    private function normalizarEstado(string $estado): string
    {
        return $estado === 'En tránsito' ? 'En transito' : $estado;
    }

    private function buscarProgramacion(int $id): ?array
    {
        foreach (['programaciones_viajes', 'programacion_viajes', 'programaciones', 'viajes_programados', 'viajes'] as $tabla) {
            try {
                $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=logistica_rutas;charset=utf8', 'root', '');
                $stmt = $pdo->prepare("SELECT * FROM {$tabla} WHERE id = ? LIMIT 1");
                $stmt->execute([$id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($row) {
                    return $row;
                }
            } catch (PDOException) {
                continue;
            }
        }

        return null;
    }
}
