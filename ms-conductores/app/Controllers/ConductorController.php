<?php
namespace App\Controllers;

use App\Helpers\JsonResponse;
use App\Models\Conductor;
use Carbon\Carbon;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ConductorController
{
    private const ESTADOS = ['Disponible', 'En ruta', 'Inactivo'];

    public function index(Request $request, Response $response): Response
    {
        $query = Conductor::query();
        $params = $request->getQueryParams();

        if (!empty($params['documento'])) {
            $query->where('documento', 'like', '%' . trim($params['documento']) . '%');
        }

        if (!empty($params['licencia'])) {
            $query->where('numero_licencia', 'like', '%' . trim($params['licencia']) . '%');
        }

        if (!empty($params['estado'])) {
            $query->where('estado', trim($params['estado']));
        }

        return JsonResponse::ok($response, $query->orderBy('id', 'desc')->get());
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $conductor = Conductor::find($args['id']);

        if (!$conductor) {
            return JsonResponse::error($response, 'Conductor no encontrado', 404);
        }

        return JsonResponse::ok($response, $conductor);
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $this->datos($request);
        $error = $this->validar($data);

        if ($error) {
            return JsonResponse::error($response, $error, 422);
        }

        if (Conductor::where('documento', $data['documento'])->exists()) {
            return JsonResponse::error($response, 'El documento ya existe', 409);
        }

        if (Conductor::where('numero_licencia', $data['numero_licencia'])->exists()) {
            return JsonResponse::error($response, 'La licencia ya existe', 409);
        }

        $conductor = Conductor::create($data);

        return JsonResponse::ok($response, $conductor, 201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $conductor = Conductor::find($args['id']);

        if (!$conductor) {
            return JsonResponse::error($response, 'Conductor no encontrado', 404);
        }

        $data = $this->datos($request, false);
        $error = $this->validar(array_merge($conductor->toArray(), $data), false);

        if ($error) {
            return JsonResponse::error($response, $error, 422);
        }

        if (isset($data['documento']) && Conductor::where('documento', $data['documento'])->where('id', '!=', $conductor->id)->exists()) {
            return JsonResponse::error($response, 'El documento ya existe', 409);
        }

        if (isset($data['numero_licencia']) && Conductor::where('numero_licencia', $data['numero_licencia'])->where('id', '!=', $conductor->id)->exists()) {
            return JsonResponse::error($response, 'La licencia ya existe', 409);
        }

        $conductor->update($data);

        return JsonResponse::ok($response, $conductor->fresh());
    }

    public function cambiarEstado(Request $request, Response $response, array $args): Response
    {
        $conductor = Conductor::find($args['id']);

        if (!$conductor) {
            return JsonResponse::error($response, 'Conductor no encontrado', 404);
        }

        $body = (array) $request->getParsedBody();
        $estado = trim($body['estado'] ?? '');

        if (!in_array($estado, self::ESTADOS, true)) {
            return JsonResponse::error($response, 'Estado invalido', 422);
        }

        $conductor->update(['estado' => $estado]);

        return JsonResponse::ok($response, $conductor->fresh());
    }

    private function datos(Request $request, bool $conDefaults = true): array
    {
        $body = (array) $request->getParsedBody();
        $campos = [
            'nombres',
            'apellidos',
            'documento',
            'telefono',
            'email',
            'numero_licencia',
            'categoria_licencia',
            'fecha_vencimiento_licencia',
            'estado',
        ];
        $data = [];

        foreach ($campos as $campo) {
            if (array_key_exists($campo, $body)) {
                $data[$campo] = is_string($body[$campo]) ? trim($body[$campo]) : $body[$campo];
            }
        }

        if ($conDefaults && empty($data['estado'])) {
            $data['estado'] = 'Disponible';
        }

        return $data;
    }

    private function validar(array $data, bool $crear = true): ?string
    {
        foreach (['nombres', 'apellidos', 'documento', 'numero_licencia', 'categoria_licencia', 'fecha_vencimiento_licencia'] as $campo) {
            if ($crear && empty($data[$campo])) {
                return "El campo {$campo} es obligatorio";
            }
        }

        if (!empty($data['correo']) && !filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
            return 'El correo no es valido';
        }

        if (!empty($data['estado']) && !in_array($data['estado'], self::ESTADOS, true)) {
            return 'Estado invalido';
        }

        if (!empty($data['fecha_vencimiento_licencia'])) {
            try {
                if (Carbon::parse($data['fecha_vencimiento_licencia'])->isPast()) {
                    return 'La licencia esta vencida';
                }
            } catch (\Throwable) {
                return 'La fecha de vencimiento no es valida';
            }
        }

        return null;
    }
}
