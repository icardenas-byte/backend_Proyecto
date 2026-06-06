<?php
namespace App\Controllers;

use App\Helpers\JsonResponse;
use App\Models\Ruta;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class RutaController
{
    public function index(Request $request, Response $response): Response
    {
        $query = Ruta::query();
        $params = $request->getQueryParams();

        if (!empty($params['ciudad'])) {
            $ciudad = trim($params['ciudad']);
            $query->where(function ($q) use ($ciudad) {
                $q->where('ciudad_origen', 'like', "%{$ciudad}%")
                    ->orWhere('ciudad_destino', 'like', "%{$ciudad}%");
            });
        }

        return JsonResponse::ok($response, $query->orderBy('id', 'desc')->get());
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $ruta = Ruta::find($args['id']);

        if (!$ruta) {
            return JsonResponse::error($response, 'Ruta no encontrada', 404);
        }

        return JsonResponse::ok($response, $ruta);
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $this->datos($request);
        $error = $this->validar($data);

        if ($error) {
            return JsonResponse::error($response, $error, 422);
        }

        if (Ruta::where('ciudad_origen', $data['ciudad_origen'])->where('ciudad_destino', $data['ciudad_destino'])->exists()) {
            return JsonResponse::error($response, 'La ruta ya existe', 409);
        }

        return JsonResponse::ok($response, Ruta::create($data), 201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $ruta = Ruta::find($args['id']);

        if (!$ruta) {
            return JsonResponse::error($response, 'Ruta no encontrada', 404);
        }

        $data = $this->datos($request, false);
        $error = $this->validar(array_merge($ruta->toArray(), $data), false);

        if ($error) {
            return JsonResponse::error($response, $error, 422);
        }

        if ((isset($data['ciudad_origen']) || isset($data['ciudad_destino'])) && Ruta::where('ciudad_origen', $data['ciudad_origen'] ?? $ruta->ciudad_origen)->where('ciudad_destino', $data['ciudad_destino'] ?? $ruta->ciudad_destino)->where('id', '!=', $ruta->id)->exists()) {
            return JsonResponse::error($response, 'La ruta ya existe', 409);
        }

        $ruta->update($data);

        return JsonResponse::ok($response, $ruta->fresh());
    }

    private function datos(Request $request, bool $crear = true): array
    {
        $body = (array) $request->getParsedBody();
        $data = [];

        foreach (['ciudad_origen', 'ciudad_destino', 'distancia', 'tiempo_estimado', 'observaciones'] as $campo) {
            if (array_key_exists($campo, $body)) {
                $data[$campo] = is_string($body[$campo]) ? trim($body[$campo]) : $body[$campo];
            }
        }

        return $data;
    }

    private function validar(array $data, bool $crear = true): ?string
    {
        foreach (['ciudad_origen', 'ciudad_destino', 'distancia', 'tiempo_estimado'] as $campo) {
            if ($crear && empty($data[$campo])) {
                return "El campo {$campo} es obligatorio";
            }
        }

        if (isset($data['distancia']) && (float) $data['distancia'] <= 0) {
            return 'La distancia debe ser mayor a cero';
        }

        return null;
    }
}
