<?php
namespace App\Controllers;

use App\Helpers\JsonResponse;
use App\Models\ProgramacionViaje;
use App\Models\Ruta;
use PDO;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ProgramacionController
{
    private const ESTADOS_ACTIVOS = ['Programado', 'En transito', 'Retrasado'];

    public function index(Request $request, Response $response): Response
    {
        $query = ProgramacionViaje::query();
        $params = $request->getQueryParams();

        foreach (['conductor_id', 'vehiculo_id', 'estado', 'fecha_salida'] as $campo) {
            if (!empty($params[$campo])) {
                $query->where($campo, trim($params[$campo]));
            }
        }

        if (!empty($params['fecha'])) {
            $query->where('fecha_salida', trim($params['fecha']));
        }

        return JsonResponse::ok($response, $query->with('ruta')->orderBy('id', 'desc')->get());
    }

    public function store(Request $request, Response $response): Response{
    $data = $this->datos($request);
    $error = $this->validar($data);
    if ($error) {
        return JsonResponse::error($response, $error, 422);
    }
    $errorDisponibilidad = $this->validarDisponibilidad($data);
    if ($errorDisponibilidad) {
        return JsonResponse::error($response, $errorDisponibilidad, 409);
    }
    $programacion = ProgramacionViaje::create($data);
    try {
        $pdo = new PDO(
            'mysql:host=127.0.0.1;port=3306;dbname=logistica_viajes;charset=utf8',
            'root',
            ''
        );
        $stmt = $pdo->prepare("
            INSERT INTO seguimientos_viajes (
                programacion_viaje_id,
                fecha,
                hora,
                estado,
                novedad,
                created_at,
                updated_at
            )
            VALUES (
                ?, ?, ?, ?, ?, NOW(), NOW()
            )
        ");
        $stmt->execute([
            $programacion->id,
            $programacion->fecha_salida,
            $programacion->hora_salida,
            'programado',
            'Viaje programado'
        ]);

    } catch (PDOException $e) {

        return JsonResponse::error(
            $response,
            'Error creando seguimiento: ' . $e->getMessage(),
            500
        );

    }

    return JsonResponse::ok($response, $programacion, 201);
}
    public function update(Request $request, Response $response, array $args): Response
    {
        $programacion = ProgramacionViaje::find($args['id']);

        if (!$programacion) {
            return JsonResponse::error($response, 'Programacion no encontrada', 404);
        }

        $data = $this->datos($request, false);
        $final = array_merge($programacion->toArray(), $data);
        $error = $this->validar($final, false);

        if ($error) {
            return JsonResponse::error($response, $error, 422);
        }

        $errorDisponibilidad = $this->validarDisponibilidad($final, (int) $programacion->id);
        if ($errorDisponibilidad) {
            return JsonResponse::error($response, $errorDisponibilidad, 409);
        }

        $programacion->update($data);

        return JsonResponse::ok($response, $programacion->fresh());
    }

    private function datos(Request $request, bool $conDefaults = true): array
    {
        $body = (array) $request->getParsedBody();
        $data = [];

        foreach (['conductor_id', 'vehiculo_id', 'ruta_id', 'fecha_salida', 'hora_salida', 'fecha_estimada_llegada', 'observaciones', 'estado'] as $campo) {
            if (array_key_exists($campo, $body)) {
                $data[$campo] = is_string($body[$campo]) ? trim($body[$campo]) : $body[$campo];
            }
        }

        if ($conDefaults && empty($data['estado'])) {
            $data['estado'] = 'Programado';
        }

        return $data;
    }

    private function validar(array $data, bool $crear = true): ?string
    {
        foreach (['conductor_id', 'vehiculo_id', 'ruta_id', 'fecha_salida', 'hora_salida', 'fecha_estimada_llegada'] as $campo) {
            if ($crear && empty($data[$campo])) {
                return "El campo {$campo} es obligatorio";
            }
        }

        if (!empty($data['ruta_id']) && !Ruta::find($data['ruta_id'])) {
            return 'La ruta no existe';
        }

        if (!empty($data['fecha_salida']) && !empty($data['fecha_estimada_llegada']) && $data['fecha_estimada_llegada'] < $data['fecha_salida']) {
            return 'La llegada estimada no puede ser anterior a la salida';
        }

        return null;
    }

    private function validarDisponibilidad(array $data, ?int $ignorarId = null): ?string
    {
        $conductor = $this->buscarExterno('logistica_conductores', 'conductores', (int) $data['conductor_id']);
        if (!$conductor) {
            return 'El conductor no existe';
        }

        if (($conductor['estado'] ?? '') === 'Inactivo') {
            return 'No se puede programar un conductor inactivo';
        }

        $vehiculo = $this->buscarExterno('logistica_vehiculos', 'vehiculos', (int) $data['vehiculo_id']);
        if (!$vehiculo) {
            return 'El vehiculo no existe';
        }

        if (in_array($vehiculo['estado'] ?? '', ['Mantenimiento', 'Inactivo'], true)) {
            return 'El vehiculo no esta disponible';
        }

        $ocupado = ProgramacionViaje::where('fecha_salida', $data['fecha_salida'])
            ->whereIn('estado', self::ESTADOS_ACTIVOS)
            ->where(function ($query) use ($data) {
                $query->where('conductor_id', $data['conductor_id'])
                    ->orWhere('vehiculo_id', $data['vehiculo_id']);
            });

        if ($ignorarId) {
            $ocupado->where('id', '!=', $ignorarId);
        }

        return $ocupado->exists() ? 'Conductor o vehiculo no disponible en esa fecha' : null;
    }

    private function buscarExterno(string $database, string $tabla, int $id): ?array
    {
        try {
            $pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname={$database};charset=utf8", 'root', '');
            $stmt = $pdo->prepare("SELECT * FROM {$tabla} WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException) {
            return null;
        }
    }
}
