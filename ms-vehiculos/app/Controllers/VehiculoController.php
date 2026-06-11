<?php
namespace App\Controllers;
use App\Helpers\JsonResponse;
use App\Models\Vehiculo;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class VehiculoController
{
    private const ESTADOS = ['Disponible', 'En ruta', 'Mantenimiento', 'Inactivo'];

    public function index(Request $request, Response $response): Response
    {
        $query = Vehiculo::query();
        $params = $request->getQueryParams();

        if (!empty($params['placa'])) {
            $query->where('placa', 'like', '%' . strtoupper(trim($params['placa'])) . '%');
        }

        if (!empty($params['estado'])) {
            $query->where('estado', trim($params['estado']));
        }

        if (!empty($params['tipo'])) {
            $query->where('tipo', 'like', '%' . trim($params['tipo']) . '%');
        }

        return JsonResponse::ok($response, $query->orderBy('id', 'desc')->get());
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $vehiculo = Vehiculo::find($args['id']);

        if (!$vehiculo) {
            return JsonResponse::error($response, 'Vehiculo no encontrado', 404);
        }

        return JsonResponse::ok($response, $vehiculo);
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $this->datos($request);
        $error = $this->validar($data);

        if ($error) {
            return JsonResponse::error($response, $error, 422);
        }

        if (Vehiculo::where('placa', $data['placa'])->exists()) {
            return JsonResponse::error($response, 'La placa ya existe', 409);
        }

        return JsonResponse::ok($response, Vehiculo::create($data), 201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $vehiculo = Vehiculo::find($args['id']);

        if (!$vehiculo) {
            return JsonResponse::error($response, 'Vehiculo no encontrado', 404);
        }

        $data = $this->datos($request, false);
        $error = $this->validar(array_merge($vehiculo->toArray(), $data), false);

        if ($error) {
            return JsonResponse::error($response, $error, 422);
        }

        if (isset($data['placa']) && Vehiculo::where('placa', $data['placa'])->where('id', '!=', $vehiculo->id)->exists()) {
            return JsonResponse::error($response, 'La placa ya existe', 409);
        }

        $vehiculo->update($data);

        return JsonResponse::ok($response, $vehiculo->fresh());
    }

    public function cambiarEstado(Request $request, Response $response, array $args): Response
    {
        $vehiculo = Vehiculo::find($args['id']);

        if (!$vehiculo) {
            return JsonResponse::error($response, 'Vehiculo no encontrado', 404);
        }

        $body = (array) $request->getParsedBody();
        $estado = trim($body['estado'] ?? '');

        if (!in_array($estado, self::ESTADOS, true)) {
            return JsonResponse::error($response, 'Estado invalido', 422);
        }

        $vehiculo->update(['estado' => $estado]);

        return JsonResponse::ok($response, $vehiculo->fresh());
    }

    private function datos(Request $request, bool $conDefaults = true): array
    {
        $body = (array) $request->getParsedBody();
        $data = [];

        foreach (['placa', 'tipo_vehiculo', 'capacidad_carga', 'modelo', 'marca', 'estado'] as $campo) {
            if (array_key_exists($campo, $body)) {
                $data[$campo] = is_string($body[$campo]) ? trim($body[$campo]) : $body[$campo];
            }
        }

        if (isset($data['placa'])) {
            $data['placa'] = strtoupper($data['placa']);
        }

        if ($conDefaults && empty($data['estado'])) {
            $data['estado'] = 'Disponible';
        }

        return $data;
    }

    private function validar(array $data, bool $crear = true): ?string
    {
        foreach (['placa', 'tipo_vehiculo', 'capacidad_carga', 'modelo', 'marca'] as $campo) {
            if ($crear && empty($data[$campo])) {
                return "El campo {$campo} es obligatorio";
            }
        }

        if (isset($data['capacidad_carga']) && (float) $data['capacidad_carga'] <= 0) {
            return 'La capacidad debe ser mayor a cero';
        }

        if (!empty($data['estado']) && !in_array($data['estado'], self::ESTADOS, true)) {
            return 'Estado invalido';
        }

        return null;
    }
}
