<?php
namespace App\Controllers;
use App\Helpers\JsonResponse;
use App\Models\Viaje;
use PDO;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ViajeController
{
    private const ESTADOS = [
        'programado',
        'en_transito',
        'retrasado',
        'finalizado',
        'cancelado'
    ];
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
        return JsonResponse::ok(
            $response,
            $query->orderBy('id', 'desc')->get()
        );
    }
    public function iniciar(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $programacionId = (int)($body['programacion_viaje_id'] ?? 0);
        if ($programacionId <= 0) {
            return JsonResponse::error(
                $response,
                'programacion_viaje_id es obligatorio',
                422
            );
        }
        $programacion = $this->buscarProgramacion($programacionId);
        if (!$programacion) {
            return JsonResponse::error(
                $response,
                'La programacion no existe',
                404
            );
        }
        if (($programacion['estado'] ?? '') === 'cancelado') {
            return JsonResponse::error(
                $response,
                'No se puede iniciar un viaje cancelado',
                409
            );
        }
        $viaje = Viaje::where(
            'programacion_viaje_id',
            $programacionId
        )->first();
        if (!$viaje) {
            return JsonResponse::error(
                $response,
                'No existe seguimiento para esta programacion',
                404
            );
        }
        if ($viaje->estado === 'cancelado') {
            return JsonResponse::error(
                $response,
                'No se puede iniciar un viaje cancelado',
                409
            );
        }
        if ($viaje->estado === 'finalizado') {
            return JsonResponse::error(
                $response,
                'El viaje ya esta finalizado',
                409
            );
        }
        $viaje->update([
            'estado' => 'en_transito',
            'novedad' => 'Viaje iniciado'
        ]);

        return JsonResponse::ok(
            $response,
            $viaje->fresh()
        );
    }

    public function actualizarEstado(
        Request $request,
        Response $response,
        array $args
    ): Response {
        $viaje = Viaje::find($args['id']);
        if (!$viaje) {
            return JsonResponse::error(
                $response,
                'Viaje no encontrado',
                404
            );
        }
        $body = (array)$request->getParsedBody();
        $estado = $this->normalizarEstado(
            trim($body['estado'] ?? '')
        );
        if (!in_array($estado, self::ESTADOS, true)) {
            return JsonResponse::error(
                $response,
                'Estado invalido',
                422
            );
        }
        if ($viaje->estado === 'finalizado') {
            return JsonResponse::error(
                $response,
                'No se puede modificar un viaje finalizado',
                409
            );
        }
        $viaje->update([
            'estado' => $estado,
            'novedad' => 'Estado cambiado a ' . $estado
        ]);
        return JsonResponse::ok(
            $response,
            $viaje->fresh()
        );
    }

    public function registrarNovedad(
        Request $request,
        Response $response,
        array $args
    ): Response {

        $viaje = Viaje::find($args['id']);
        if (!$viaje) {
            return JsonResponse::error(
                $response,
                'Viaje no encontrado',
                404
            );
        }
        $body = (array)$request->getParsedBody();
        $novedad = trim($body['novedad'] ?? '');
        if ($novedad === '') {
            return JsonResponse::error(
                $response,
                'La novedad es obligatoria',
                422
            );
        }
        $viaje->update([
            'novedad' => $novedad
        ]);
        if (
            stripos($novedad, 'retraso') !== false
            && $viaje->estado === 'en_transito'
        ) {
            $viaje->update([
                'estado' => 'retrasado'
            ]);
        }
        return JsonResponse::ok(
            $response,
            $viaje->fresh(),
            201
        );
    }

    public function finalizar(
        Request $request,
        Response $response,
        array $args
    ): Response {
        $viaje = Viaje::find($args['id']);
        if (!$viaje) {
            return JsonResponse::error(
                $response,
                'Viaje no encontrado',
                404
            );
        }
        if (
            !in_array(
                $viaje->estado,
                ['en_transito', 'retrasado'],
                true
            )
        ) {
            return JsonResponse::error(
                $response,
                'No se puede finalizar un viaje no iniciado',
                409
            );
        }
        $viaje->update([
            'estado' => 'finalizado',
            'novedad' => 'Viaje finalizado'
        ]);
        return JsonResponse::ok(
            $response,
            $viaje->fresh()
        );
    }

    public function seguimiento(
        Request $request,
        Response $response,
        array $args
    ): Response {
        $viaje = Viaje::find($args['id']);
        if (!$viaje) {
            return JsonResponse::error(
                $response,
                'Viaje no encontrado',
                404
            );
        }
        $seguimientos = Viaje::where(
            'programacion_viaje_id',
            $viaje->programacion_viaje_id
        )
        ->orderBy('fecha')
        ->orderBy('hora')
        ->get();
        return JsonResponse::ok(
            $response,
            $seguimientos
        );
    }

    private function normalizarEstado(string $estado): string
    {
        return trim(strtolower($estado));
    }

    private function buscarProgramacion(int $id): ?array
    {
        foreach (
            [
                'programaciones_viajes',
                'programacion_viajes',
                'programaciones',
                'viajes_programados',
                'viajes'
            ] as $tabla
        ) {
            try {
                $pdo = new PDO(
                    'mysql:host=127.0.0.1;port=3306;dbname=logistica_rutas;charset=utf8',
                    'root',
                    ''
                );
                $stmt = $pdo->prepare(
                    "SELECT * FROM {$tabla} WHERE id = ? LIMIT 1"
                );
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